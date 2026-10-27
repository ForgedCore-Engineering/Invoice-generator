"""Isolated integration fixture only. Usage: python tools/test-footprints.py PHP_EXECUTABLE.
Requires localhost Apache :8098 and disposable MariaDB :33317/footprint_test.
Uses dummy accounts, never production credentials or records.
"""
import sys, json, re, subprocess, urllib.request, urllib.error, urllib.parse, http.cookiejar
php=sys.argv[1];base='http://127.0.0.1:8098/';checks=0
class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self,*a,**kw):return None
jar=http.cookiejar.CookieJar();op=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar),NoRedirect())
def check(value,message):
    global checks
    assert value,message
    checks+=1
def db(sql,params=None):
    script='$v=json_decode(stream_get_contents(STDIN),true);$p=new PDO("mysql:host=127.0.0.1;port=33317;dbname=footprint_test;charset=utf8mb4","root","",[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);$s=$p->prepare($v["sql"]);$s->execute($v["params"]);echo json_encode($s->columnCount()?$s->fetchAll():[]);'
    result=subprocess.run([php,'-r',script],input=json.dumps({'sql':sql,'params':params or []}),text=True,capture_output=True,check=True)
    return json.loads(result.stdout)
def request(path,data=None,as_json=False):
    headers={};body=None
    if data is not None:
        body=(json.dumps(data) if as_json else urllib.parse.urlencode(data)).encode()
        headers['Content-Type']='application/json' if as_json else 'application/x-www-form-urlencoded'
    try:r=op.open(urllib.request.Request(base+path,data=body,headers=headers))
    except urllib.error.HTTPError as e:r=e
    text=r.read().decode('utf-8',errors='replace')
    check(not re.search(r'(Fatal error:|Warning:|Notice:|Parse error:)',text),'No PHP diagnostics in response')
    return r.code,text
def login(second=False):
    jar.clear();status,text=request('login.php');token=re.search('name="csrf" value="([^"]+)"',text).group(1)
    status,text=request('login.php',{'csrf':token,'email':('second' if second else 'test')+'@forgedcoreengineering.com','password':'Second-test-only-123!' if second else 'Example-test-only-123!'})
    check(status==303,'Dummy user signed in')
def post(path,payload):
    status,text=request(path,payload,True);check(status==200,'Successful mutation '+path);return json.loads(text)
def events(entity,record):return db('SELECT * FROM app_activity WHERE entity=? AND record_id=? ORDER BY id',[entity,record])
def exercise(entity):
    login();is_receipt=entity=='receipt'
    data=({'name':'Footprint receipt','address':'Test','contact':'000','description':'Test','total':100,'paid':10,'date':'2026-10-27','invoice_no':'TEST/FOOTPRINT/1'} if is_receipt else {'full_name':'Footprint payslip','service':'Test','amount_due':100,'amount_paid':10,'issue_date':'2026-10-27','payslip_no':'TEST/FOOTPRINT/1'})
    data['actor_email']='spoof@invalid.example';record=post('save-'+entity+'.php',data)['id'];history=events(entity,record)
    check(len(history)==1 and history[0]['actor_email']=='test@forgedcoreengineering.com','Creator identity comes from session')
    login(True);data['id']=record;data['description' if is_receipt else 'service']='Updated work';post('update-'+entity+'.php',data)
    history=events(entity,record);check(history[-1]['actor_email']=='second@forgedcoreengineering.com','Editor identity tracked separately')
    check(json.loads(history[-1]['before_json'])['description' if is_receipt else 'service']=='Test','Before values retained')
    before=len(history);post('update-'+entity+'.php',data);check(len(events(entity,record))==before,'Unchanged receipt/payslip update does not create noise')
    result=post('record-payment.php',{'id':record,'type':entity,'new_payment':500,'actor_email':'spoof@invalid.example'})
    check(result['applied']==90 and result['outstanding']==0,'Payment behavior preserved')
    history=events(entity,record);check(history[-1]['action']=='payment' and history[-1]['actor_email']=='second@forgedcoreengineering.com','Payment actor captured')
    status,text=request('view-'+entity+'.php?id='+str(record));check(status==200 and 'Entered by:' in text and 'test@forgedcoreengineering.com' in text,'Original creator shown after another user edits')
    status,text=request('footprints.php?entity='+entity+'&record='+str(record));check(status==200 and 'second@forgedcoreengineering.com' in text,'Footprint view filters and displays email')
    login();post('delete-'+entity+'.php',{'id':record});history=events(entity,record)
    check(history[-1]['action']=='delete' and history[-1]['actor_email']=='test@forgedcoreengineering.com' and history[-1]['after_json'] is None,'Deletion retains actor and old data')
    status,text=request('delete-'+entity+'.php',{'id':record},True);check(status==404 and len(events(entity,record))==len(history),'Missing record produces no footprint')
exercise('receipt');exercise('payslip')
login();status,text=request('accounting.php?view=assets&new=1');token=re.search('class="acct-form".*?name="csrf" value="([^"]+)"',text,re.S).group(1)
status,text=request('accounting.php?view=assets',{'csrf':token,'id':0,'version':0,'name':'Footprint asset','quantity':1,'actor_email':'spoof@invalid.example'})
check(status==302,'Accounting create succeeds')
record=db("SELECT id FROM acct_assets WHERE name='Footprint asset' ORDER BY id DESC LIMIT 1")[0]['id']
check(events('accounting.assets',record)[-1]['actor_email']=='test@forgedcoreengineering.com','Accounting creator captured')
login(True);status,text=request('accounting.php?view=assets&edit='+str(record));token=re.search('class="acct-form".*?name="csrf" value="([^"]+)"',text,re.S).group(1)
request('accounting.php?view=assets',{'csrf':token,'id':record,'version':1,'name':'Footprint asset','quantity':2})
check(events('accounting.assets',record)[-1]['actor_email']=='second@forgedcoreengineering.com','Accounting editor captured')
request('accounting.php?view=assets',{'csrf':token,'id':record,'version':2,'action':'delete'})
check(events('accounting.assets',record)[-1]['action']=='delete','Accounting delete captured')
# Force the footprint insert to fail and prove that the business record rolls back.
db("CREATE TRIGGER footprint_test_reject BEFORE INSERT ON app_activity FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Test audit failure'")
before=db('SELECT COUNT(*) n FROM clients')[0]['n']
try:
    status,text=request('save-receipt.php',{'name':'Must roll back','invoice_no':'TEST/ROLLBACK/1','date':'2026-10-27'},True)
    check(status==500,'A footprint failure rejects the save')
    check(db('SELECT COUNT(*) n FROM clients')[0]['n']==before,'Business insert rolled back with footprint failure')
    count=db('SELECT COUNT(*) n FROM acct_assets')[0]['n'];old_audit=db('SELECT COUNT(*) n FROM acct_audit')[0]['n']
    status,text=request('accounting.php?view=assets',{'csrf':token,'id':0,'version':0,'name':'Must roll back','quantity':1})
    check('Could not save' in text,'Accounting reports footprint failure')
    check(db('SELECT COUNT(*) n FROM acct_assets')[0]['n']==count,'Accounting insert rolled back with footprint failure')
    check(db('SELECT COUNT(*) n FROM acct_audit')[0]['n']==old_audit,'Legacy audit also rolls back with footprint failure')
    status,text=request('accounting-setup.php');setup_token=re.findall('name="csrf" value="([^"]+)"',text)[-1]
    imported=db("SELECT COUNT(*) n FROM acct_engagements WHERE source_ref='fixture.import'")[0]['n']
    status,text=request('accounting-setup.php',{'csrf':setup_token,'import':1})
    check('Setup could not finish.' in text,'Import reports failed footprint')
    check(db("SELECT COUNT(*) n FROM acct_engagements WHERE source_ref='fixture.import'")[0]['n']==imported,'Import data rolls back with footprint failure')
finally:db('DROP TRIGGER footprint_test_reject')
status,text=request('accounting-setup.php',{'csrf':setup_token,'import':1});check('Accounting is ready.' in text,'Import and footprint commit together')
import_event=db("SELECT * FROM app_activity WHERE entity='accounting.workbook' ORDER BY id DESC LIMIT 1")[0]
check(import_event['actor_email']=='second@forgedcoreengineering.com' and import_event['action']=='import','Import operator captured without attributing historical authorship')
print(f'{checks} footprint integration checks passed.')
