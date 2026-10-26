"""Auth HTTP regression checks against the isolated fixture described in AUTH_SETUP.md.
Usage: python tools/test-auth.py http://127.0.0.1:8098/
The fixture must have ONLY the dummy account below and receipts/test.pdf.
Never target production: this exercises sign-in failure throttling.
"""
import sys, urllib.request, urllib.parse, urllib.error, http.cookiejar, re, json
base=sys.argv[1]
assert base.startswith('http://127.0.0.1:'), 'Use an isolated local fixture only'
class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self,*a,**kw):return None
jar=http.cookiejar.CookieJar()
op=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar),NoRedirect())
checks=0
def req(path,data=None,headers=None):
    global checks
    body=urllib.parse.urlencode(data).encode() if data is not None else None
    try:response=op.open(urllib.request.Request(base+path,data=body,headers=headers or {}))
    except urllib.error.HTTPError as error:response=error
    text=response.read().decode('utf-8',errors='replace');checks+=1
    assert not re.search(r'(Warning:|Fatal error:|Parse error:|Notice:)',text),path
    return response.code,response.headers,text
pages=['index.php','accounting.php','accounting-setup.php','clients.php','payslips.php','new-receipt.php','new-payslip.php','edit-receipt.php','edit-payslip.php','view-receipt.php','view-payslip.php','download.php?file=test.pdf','export-csv.php','setup-database.php','install.php','check-dependencies.php','check-extensions.php','enable-gd-helper.php','submit.php']
for path in pages:
    status,headers,text=req(path)
    assert status==302 and 'login.php' in headers.get('Location',''),path
apis=['save-receipt.php','save-payslip.php','update-receipt.php','update-payslip.php','delete-receipt.php','delete-payslip.php','record-payment.php','search-clients.php','search-outstanding.php','get-invoice-info.php','get-payslip-info.php']
for path in apis:
    status,headers,text=req(path,data={})
    assert status==401 and 'sign in' in json.loads(text)['error'].lower(),path
for path in ['auth-private/users.json','includes/header.php','auth.php','db_config.php','receipts/test.pdf']:
    status,headers,text=req(path);assert status==403,path
status,headers,text=req('login.php');assert status==200 and 'Welcome back' in text
cookie=next(c for c in jar if c.name=='forgedcore_session')
assert cookie.has_nonstandard_attr('HttpOnly') and cookie.get_nonstandard_attr('SameSite')=='Lax'
token=re.search('name="csrf" value="([^"]+)"',text).group(1)
status,headers,text=req('login.php',{'csrf':'bad','email':'test@forgedcoreengineering.com','password':'Example-test-only-123!'})
assert status==403
for email,password in [('test@evil.com','Example-test-only-123!'),('unknown@forgedcoreengineering.com','Example-test-only-123!'),('test@forgedcoreengineering.com','wrong')]:
    status,headers,text=req('login.php',{'csrf':token,'email':email,'password':password})
    assert 'Email or password is incorrect.' in text and status==200
before=cookie.value
status,headers,text=req('login.php',{'csrf':token,'email':'TEST@FORGEDCOREENGINEERING.COM','password':'Example-test-only-123!','next':'https://evil.com'})
assert status==303 and headers['Location']=='/index.php'
assert before!=next(c.value for c in jar if c.name=='forgedcore_session')
status,headers,text=req('new-receipt.php');assert status==200 and 'Sign out' in text
logout_token=re.search('action="logout.php".*?name="csrf" value="([^"]+)"',text,re.S).group(1)
status,headers,text=req('download.php?file=test.pdf');assert status==200 and text.startswith('%PDF')
status,headers,text=req('save-receipt.php',data={},headers={'Origin':'https://evil.com'});assert status==403
status,headers,text=req('logout.php');assert status==405
status,headers,text=req('signed-out.php');assert status==302 and headers['Location']=='/index.php'
status,headers,text=req('logout.php',data={'csrf':'wrong'});assert status==403
status,headers,text=req('logout.php',data={'csrf':logout_token});assert status==303 and headers['Location']=='/signed-out.php'
status,headers,text=req('signed-out.php');assert status==200 and 'You’re signed out.' in text and 'receipts/static/logo.png' in text
status,headers,text=req('assets/auth.css');assert status==200 and '.company-logo' in text
status,headers,text=req('receipts/static/logo.png');assert status==200
status,headers,text=req('new-receipt.php');assert status==302
for attempt in range(11):
    status,headers,text=req('login.php');token=re.search('name="csrf" value="([^"]+)"',text).group(1)
    status,headers,text=req('login.php',{'csrf':token,'email':'test@forgedcoreengineering.com','password':'wrong'})
    if status==429:break
    jar.clear()
assert status==429 and 'Too many attempts' in text
print(f'{checks} authentication HTTP checks passed, including Apache file protection.')
