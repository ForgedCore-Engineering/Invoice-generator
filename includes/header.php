<?php
$current_page = basename($_SERVER['PHP_SELF']);
?><!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0,viewport-fit=cover">
<meta name="description" content="ForgedCore Engineering Ltd — Receipt Management System">
<title><?= htmlspecialchars($page_title ?? 'Receipt Manager') ?> · ForgedCore</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<!-- Anti-flash: apply saved theme BEFORE render -->
<script>
(function(){
  var t = localStorage.getItem('fc-theme') || 'dark';
  document.documentElement.setAttribute('data-theme', t);
})();
</script>

<style>
/* ════════════════════════════════════════════
   RESET
════════════════════════════════════════════ */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}

/* ════════════════════════════════════════════
   THEME TOKENS
════════════════════════════════════════════ */
:root,[data-theme="dark"]{
  --bg:        #070C18;
  --bg2:       #0D1525;
  --card:      #121C32;
  --card-h:    #172038;
  --inp:       #0A1020;
  --br:        #1B2B48;
  --br2:       #243D65;
  --sh:        0 4px 20px rgba(0,0,0,.5);
  --sh-sm:     0 2px 8px rgba(0,0,0,.35);
  --txt:       #F1F5F9;
  --txt2:      #94A3B8;
  --txt3:      #64748B;
  --acc:       #009999;
  --acc-l:     #00B2B2;
  --acc-d:     #008080;
  --acc-bg:    rgba(0,153,153,.12);
  --blue:      #3B82F6;
  --blue-bg:   rgba(59,130,246,.12);
  --green:     #10B981;
  --grn-bg:    rgba(16,185,129,.12);
  --red:       #EF4444;
  --red-bg:    rgba(239,68,68,.12);
  --ylw:       #EAB308;
  --ylw-bg:    rgba(234,179,8,.12);
  color-scheme: dark;
}

[data-theme="light"]{
  --bg:        #EFF2F7;
  --bg2:       #FFFFFF;
  --card:      #FFFFFF;
  --card-h:    #F8FAFC;
  --inp:       #F1F5F9;
  --br:        #E2E8F0;
  --br2:       #CBD5E1;
  --sh:        0 4px 20px rgba(0,0,0,.08);
  --sh-sm:     0 2px 8px rgba(0,0,0,.06);
  --txt:       #0F172A;
  --txt2:      #334155;
  --txt3:      #64748B;
  --acc:       #008080;
  --acc-l:     #009999;
  --acc-d:     #006666;
  --acc-bg:    rgba(0,128,128,.1);
  --blue:      #2563EB;
  --blue-bg:   rgba(37,99,235,.1);
  --green:     #059669;
  --grn-bg:    rgba(5,150,105,.1);
  --red:       #DC2626;
  --red-bg:    rgba(220,38,38,.1);
  --ylw:       #CA8A04;
  --ylw-bg:    rgba(202,138,4,.1);
  color-scheme: light;
}

/* ════════════════════════════════════════════
   LAYOUT CONSTANTS
════════════════════════════════════════════ */
:root{
  --sw:    256px;   /* sidebar width */
  --hh:    64px;    /* header height */
  --bnh:   62px;    /* bottom nav height */
  --r:     12px;
  --rs:    8px;
  --rss:   6px;
}

/* ════════════════════════════════════════════
   BASE
════════════════════════════════════════════ */
html,body{height:100%}
body{
  font-family:'Inter',sans-serif;
  background:var(--bg);
  color:var(--txt);
  line-height:1.6;
  overflow-x:hidden;
  transition:background .2s,color .2s;
}
a{text-decoration:none;color:inherit}
button,select,input,textarea{font-family:'Inter',sans-serif}

/* ════════════════════════════════════════════
   APP WRAPPER
════════════════════════════════════════════ */
.app{display:flex;min-height:100vh}

/* ════════════════════════════════════════════
   SIDEBAR
════════════════════════════════════════════ */
.sb{
  width:var(--sw);
  background:var(--bg2);
  border-right:1px solid var(--br);
  position:fixed;top:0;left:0;height:100vh;
  display:flex;flex-direction:column;
  z-index:200;
  transition:transform .3s cubic-bezier(.4,0,.2,1),box-shadow .3s;
  overflow:hidden;
}
.sb.open{box-shadow:4px 0 24px rgba(0,0,0,.35)}

/* Logo */
.sb-logo{
  display:flex;align-items:center;gap:12px;
  padding:14px 16px;
  border-bottom:1px solid var(--br);
  flex-shrink:0;
  min-height:var(--hh);
}
.logo-ic{
  width:40px;height:40px;flex-shrink:0;
  display:flex;align-items:center;justify-content:center;
  font-weight:800;font-size:12.5px;color:#000;letter-spacing:.5px;
}
.logo-ic img{
  width:100%;height:100%;object-fit:contain;
}
.logo-ic.no-img{background:linear-gradient(135deg,var(--acc),var(--acc-d));border-radius:9px;}
.logo-txt{display:flex;flex-direction:column;overflow:hidden}
.logo-n{font-size:13px;font-weight:700;color:var(--txt);line-height:1.25;white-space:nowrap}
.logo-s{font-size:10.5px;color:var(--txt3);white-space:nowrap}

/* Navigation */
.sb-nav{
  flex:1;padding:10px 8px;
  display:flex;flex-direction:column;
  overflow-y:auto;overflow-x:hidden;
  scrollbar-width:thin;scrollbar-color:var(--br) transparent;
}
.sb-nav::-webkit-scrollbar{width:4px}
.sb-nav::-webkit-scrollbar-track{background:transparent}
.sb-nav::-webkit-scrollbar-thumb{background:var(--br);border-radius:4px}

/* Accordion Group */
.nav-acc{overflow:hidden}
.nav-acc-btn{
  display:flex;align-items:center;justify-content:space-between;
  width:100%;background:none;border:none;cursor:pointer;
  padding:10px 10px 4px;
  font-size:10px;font-weight:700;color:var(--txt3);
  text-transform:uppercase;letter-spacing:1px;
  transition:color .15s;
  margin-top:4px;
}
.nav-acc-btn:first-child{margin-top:0}
.nav-acc-btn:hover{color:var(--txt2)}
.nav-acc-btn .chv{
  width:14px;height:14px;
  transition:transform .25s ease;
  flex-shrink:0;color:var(--txt3);
}
.nav-acc.closed .chv{transform:rotate(-90deg)}
.nav-items{
  overflow:hidden;
  max-height:300px;
  transition:max-height .3s cubic-bezier(.4,0,.2,1),opacity .25s;
  opacity:1;
  display:flex;flex-direction:column;gap:2px;
  padding:0 0 4px;
}
.nav-acc.closed .nav-items{max-height:0;opacity:0}

/* Nav Item */
.ni{
  display:flex;align-items:center;gap:10px;
  padding:9px 10px;border-radius:var(--rs);
  color:var(--txt2);font-size:13px;font-weight:500;
  transition:background .15s,color .15s;
  position:relative;white-space:nowrap;
}
.ni:hover{background:var(--card-h);color:var(--txt)}
.ni.active{background:var(--acc-bg);color:var(--acc)}
.ni.active::before{
  content:'';position:absolute;left:0;top:7px;bottom:7px;
  width:3px;background:var(--acc);border-radius:0 3px 3px 0;
}
.ni svg.ni-icon{width:17px;height:17px;flex-shrink:0}
.ni-badge{
  margin-left:auto;background:var(--acc-bg);color:var(--acc);
  font-size:10px;font-weight:700;padding:2px 7px;border-radius:20px;
}

/* Sidebar Footer */
.sb-foot{
  padding:12px 16px;border-top:1px solid var(--br);flex-shrink:0;
}
.sb-foot-txt{font-size:10.5px;color:var(--txt3);line-height:1.8}
.sb-foot-txt strong{color:var(--txt2)}

/* ════════════════════════════════════════════
   MAIN CONTENT
════════════════════════════════════════════ */
.main{
  flex:1;margin-left:var(--sw);
  display:flex;flex-direction:column;
  min-height:100vh;
}

/* ════════════════════════════════════════════
   TOP HEADER
════════════════════════════════════════════ */
.hdr{
  height:var(--hh);
  background:var(--bg2);border-bottom:1px solid var(--br);
  display:flex;align-items:center;padding:0 20px;gap:10px;
  position:sticky;top:0;z-index:100;
  transition:background .2s,border-color .2s;
}
.menu-btn{
  display:none;background:none;border:none;color:var(--txt2);
  cursor:pointer;padding:7px;border-radius:var(--rs);
  align-items:center;justify-content:center;
  transition:background .15s,color .15s;flex-shrink:0;
}
.menu-btn:hover{background:var(--card-h);color:var(--txt)}
.ph{flex:1;min-width:0}
.ph h1{font-size:15px;font-weight:600;color:var(--txt);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ph p{font-size:11px;color:var(--txt3)}
.hdr-acts{display:flex;align-items:center;gap:6px;flex-shrink:0}

/* Theme Toggle Button */
.theme-btn{
  width:36px;height:36px;
  border-radius:var(--rs);border:1px solid var(--br);
  background:var(--card);color:var(--txt2);
  cursor:pointer;
  display:flex;align-items:center;justify-content:center;
  transition:background .15s,border-color .15s,color .15s;
  flex-shrink:0;
}
.theme-btn:hover{background:var(--card-h);border-color:var(--br2);color:var(--txt)}
.theme-btn svg{width:17px;height:17px;transition:opacity .15s}
.theme-btn .icon-sun{display:none}
.theme-btn .icon-moon{display:block}
[data-theme="light"] .theme-btn .icon-sun{display:block}
[data-theme="light"] .theme-btn .icon-moon{display:none}

/* ════════════════════════════════════════════
   PAGE CONTENT
════════════════════════════════════════════ */
.pg{flex:1;padding:22px 24px}

/* ════════════════════════════════════════════
   BUTTONS
════════════════════════════════════════════ */
.btn{
  display:inline-flex;align-items:center;gap:7px;
  padding:8px 15px;border-radius:var(--rs);
  font-size:13px;font-weight:500;
  border:none;cursor:pointer;transition:all .15s;
  white-space:nowrap;line-height:1;
}
.btn svg{width:14px;height:14px;flex-shrink:0}
.btn-p{background:var(--acc);color:#fff}
.btn-p:hover{background:var(--acc-l);transform:translateY(-1px);box-shadow:0 4px 12px rgba(0,153,153,.3)}
.btn-s{background:var(--card);color:var(--txt);border:1px solid var(--br)}
.btn-s:hover{background:var(--card-h);border-color:var(--br2)}
.btn-d{background:var(--red-bg);color:var(--red);border:1px solid rgba(239,68,68,.2)}
.btn-d:hover{background:var(--red);color:#fff}
.btn-g{background:var(--grn-bg);color:var(--green);border:1px solid rgba(16,185,129,.2)}
.btn-g:hover{background:var(--green);color:#fff}
.btn-b{background:var(--blue-bg);color:var(--blue);border:1px solid rgba(59,130,246,.2)}
.btn-b:hover{background:var(--blue);color:#fff}
.btn-sm{padding:5px 11px;font-size:12px}
.btn-lg{padding:11px 20px;font-size:14px}
.btn:disabled{opacity:.45;cursor:not-allowed;transform:none!important;box-shadow:none!important}

/* ════════════════════════════════════════════
   CARDS
════════════════════════════════════════════ */
.card{
  background:var(--card);border:1px solid var(--br);
  border-radius:var(--r);overflow:hidden;
  box-shadow:var(--sh-sm);
  transition:background .2s,border-color .2s;
}
.card-hdr{
  padding:14px 20px;border-bottom:1px solid var(--br);
  display:flex;align-items:center;justify-content:space-between;gap:12px;
}
.card-title{font-size:14px;font-weight:600;color:var(--txt)}
.card-sub{font-size:11px;color:var(--txt3);margin-top:2px}
.card-body{padding:20px}

/* ════════════════════════════════════════════
   STAT CARDS
════════════════════════════════════════════ */
.sg{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px}
.sc{
  background:var(--card);border:1px solid var(--br);border-radius:var(--r);
  padding:18px 20px;position:relative;overflow:hidden;
  box-shadow:var(--sh-sm);transition:background .2s,border-color .2s;
}
.sc-bar{height:3px;margin:-18px -20px 16px;border-radius:var(--r) var(--r) 0 0}
.sc.am .sc-bar{background:linear-gradient(90deg,var(--acc),var(--acc-l))}
.sc.bl .sc-bar{background:linear-gradient(90deg,var(--blue),#60A5FA)}
.sc.gn .sc-bar{background:linear-gradient(90deg,var(--green),#34D399)}
.sc.rd .sc-bar{background:linear-gradient(90deg,var(--red),#F87171)}
.sc-ic{width:36px;height:36px;border-radius:9px;display:flex;align-items:center;justify-content:center;margin-bottom:12px}
.sc-ic svg{width:17px;height:17px}
.sc.am .sc-ic{background:var(--acc-bg);color:var(--acc)}
.sc.bl .sc-ic{background:var(--blue-bg);color:var(--blue)}
.sc.gn .sc-ic{background:var(--grn-bg);color:var(--green)}
.sc.rd .sc-ic{background:var(--red-bg);color:var(--red)}
.sc-val{font-size:20px;font-weight:700;color:var(--txt);line-height:1;margin-bottom:5px}
.sc-lbl{font-size:11.5px;color:var(--txt3)}

/* ════════════════════════════════════════════
   TABLE
════════════════════════════════════════════ */
.tw{overflow-x:auto;-webkit-overflow-scrolling:touch}
table{width:100%;border-collapse:collapse;min-width:560px}
thead th{
  padding:10px 14px;text-align:left;font-size:11px;font-weight:600;
  color:var(--txt3);text-transform:uppercase;letter-spacing:.5px;
  background:rgba(0,0,0,.03);border-bottom:1px solid var(--br);white-space:nowrap;
}
[data-theme="dark"] thead th{background:rgba(255,255,255,.02)}
tbody td{padding:12px 14px;font-size:13px;border-bottom:1px solid var(--br);color:var(--txt)}
tbody tr:last-child td{border-bottom:none}
tbody tr:hover td{background:var(--card-h)}

/* ════════════════════════════════════════════
   BADGES
════════════════════════════════════════════ */
.badge{
  display:inline-flex;align-items:center;gap:5px;
  padding:3px 9px;border-radius:20px;font-size:11.5px;font-weight:600;white-space:nowrap;
}
.bdot{width:5px;height:5px;border-radius:50%;flex-shrink:0}
.bg-paid{background:var(--grn-bg);color:var(--green)}
.bg-paid .bdot{background:var(--green)}
.bg-partial{background:var(--ylw-bg);color:var(--ylw)}
.bg-partial .bdot{background:var(--ylw)}
.bg-unpaid{background:var(--red-bg);color:var(--red)}
.bg-unpaid .bdot{background:var(--red)}

/* ════════════════════════════════════════════
   FORMS
════════════════════════════════════════════ */
.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:14px}
.fg label{font-size:12px;font-weight:500;color:var(--txt2)}
.fg label.req::after{content:' *';color:var(--red)}
.fg input,.fg select,.fg textarea{
  background:var(--inp);border:1px solid var(--br);border-radius:var(--rs);
  color:var(--txt);font-size:13px;padding:9px 13px;width:100%;
  transition:border-color .15s,box-shadow .15s,background .2s;outline:none;
}
.fg input:focus,.fg select:focus,.fg textarea:focus{
  border-color:var(--acc);box-shadow:0 0 0 3px var(--acc-bg);
}
.fg textarea{resize:vertical;min-height:90px}
.fg input::placeholder,.fg textarea::placeholder{color:var(--txt3)}
.fg select option{background:var(--card);color:var(--txt)}
.fg select{
  -webkit-appearance:none;appearance:none;
  background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%2364748B'%3E%3Cpath d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
  background-repeat:no-repeat;background-position:right 10px center;background-size:18px;
  padding-right:34px;cursor:pointer;
}
.fgrid{display:grid;grid-template-columns:1fr 1fr;gap:0 16px}
.fgrid .fg.full{grid-column:1/-1}
.ipfx{position:relative}
.ipfx span{
  position:absolute;left:12px;top:50%;transform:translateY(-50%);
  color:var(--txt3);font-size:13px;pointer-events:none;
}
.ipfx input{padding-left:28px}

/* ════════════════════════════════════════════
   CLIENT CELL
════════════════════════════════════════════ */
.cc{display:flex;align-items:center;gap:10px}
.av{
  width:34px;height:34px;border-radius:50%;flex-shrink:0;
  background:linear-gradient(135deg,var(--acc-bg),var(--blue-bg));
  border:1px solid var(--br2);
  display:flex;align-items:center;justify-content:center;
  font-size:12px;font-weight:700;color:var(--acc);
}
.cn{font-weight:500;color:var(--txt);font-size:13px}
.csub{font-size:11px;color:var(--txt3)}

/* ════════════════════════════════════════════
   FILTER BAR
════════════════════════════════════════════ */
.fbar{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:14px}
.sbox{position:relative;flex:1;min-width:160px}
.sbox svg{
  position:absolute;left:10px;top:50%;transform:translateY(-50%);
  width:14px;height:14px;color:var(--txt3);pointer-events:none;
}
.sbox input{
  padding-left:32px;background:var(--inp);border:1px solid var(--br);
  border-radius:var(--rs);color:var(--txt);font-size:12.5px;
  padding-top:8px;padding-bottom:8px;outline:none;width:100%;
  transition:border-color .15s,box-shadow .15s;
}
.sbox input:focus{border-color:var(--acc);box-shadow:0 0 0 3px var(--acc-bg)}
.sbox input::placeholder{color:var(--txt3)}
.f-input{
  background:var(--inp);border:1px solid var(--br);border-radius:var(--rs);
  color:var(--txt);font-size:12.5px;padding:8px 11px;outline:none;
  transition:border-color .15s;color-scheme:inherit;min-width:0;
}
.f-input:focus{border-color:var(--acc)}
.f-sel{
  background:var(--inp);border:1px solid var(--br);border-radius:var(--rs);
  color:var(--txt);font-size:12.5px;padding:8px 28px 8px 11px;outline:none;
  cursor:pointer;min-width:120px;
  -webkit-appearance:none;appearance:none;
  background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%2364748B'%3E%3Cpath d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
  background-repeat:no-repeat;background-position:right 8px center;background-size:16px;
}

/* ════════════════════════════════════════════
   ACTIONS
════════════════════════════════════════════ */
.acts{display:flex;align-items:center;gap:5px}

/* ════════════════════════════════════════════
   PAGINATION
════════════════════════════════════════════ */
.pag{
  display:flex;align-items:center;justify-content:space-between;
  padding:12px 20px;border-top:1px solid var(--br);flex-wrap:wrap;gap:10px;
}
.pag-inf{font-size:11.5px;color:var(--txt3)}
.pag-btns{display:flex;gap:4px}
.pb{
  width:30px;height:30px;border-radius:var(--rss);border:1px solid var(--br);
  background:var(--card);color:var(--txt2);font-size:12px;cursor:pointer;
  display:inline-flex;align-items:center;justify-content:center;
  transition:all .15s;text-decoration:none;
}
.pb:hover,.pb.active{background:var(--acc-bg);color:var(--acc);border-color:var(--acc)}

/* ════════════════════════════════════════════
   EMPTY STATE
════════════════════════════════════════════ */
.empty{text-align:center;padding:52px 20px}
.empty-ic{
  width:56px;height:56px;background:var(--card-h);border-radius:50%;
  display:flex;align-items:center;justify-content:center;
  margin:0 auto 14px;color:var(--txt3);
}
.empty-ic svg{width:24px;height:24px}
.empty h3{font-size:15px;font-weight:600;color:var(--txt);margin-bottom:6px}
.empty p{font-size:13px;color:var(--txt3);margin-bottom:16px}

/* ════════════════════════════════════════════
   MODAL
════════════════════════════════════════════ */
.overlay{
  position:fixed;inset:0;background:rgba(0,0,0,.65);
  backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px);
  z-index:300;display:none;align-items:center;justify-content:center;
  padding:16px;
}
.overlay.show{display:flex}
.modal{
  background:var(--card);border:1px solid var(--br);border-radius:var(--r);
  padding:24px;width:100%;max-width:400px;
  animation:mIn .2s ease;box-shadow:var(--sh);
}
@keyframes mIn{from{opacity:0;transform:scale(.95) translateY(8px)}to{opacity:1;transform:none}}
.modal h3{font-size:15px;font-weight:600;color:var(--txt);margin-bottom:8px}
.modal p{font-size:13px;color:var(--txt2);margin-bottom:20px;line-height:1.6}
.m-acts{display:flex;justify-content:flex-end;gap:8px;flex-wrap:wrap}

/* ════════════════════════════════════════════
   TOAST
════════════════════════════════════════════ */
.tw-c{
  position:fixed;bottom:20px;right:16px;z-index:999;
  display:flex;flex-direction:column;gap:8px;
  pointer-events:none;
}
.toast{
  background:var(--card);border:1px solid var(--br);border-left:4px solid var(--green);
  border-radius:var(--rs);padding:11px 14px;
  display:flex;align-items:center;gap:9px;font-size:13px;color:var(--txt);
  box-shadow:var(--sh);animation:tIn .3s ease;
  min-width:220px;max-width:360px;pointer-events:all;
}
.toast.err{border-left-color:var(--red)}
.toast.warn{border-left-color:var(--ylw)}
@keyframes tIn{from{transform:translateX(110%);opacity:0}to{transform:translateX(0);opacity:1}}

/* ════════════════════════════════════════════
   INFO ROW (view page)
════════════════════════════════════════════ */
.info-row{display:flex;flex-wrap:wrap;gap:0}
.inf-item{flex:1;min-width:140px;padding:12px 0}
.inf-item+.inf-item{border-left:1px solid var(--br);padding-left:16px;margin-left:16px}
.inf-lbl{font-size:10.5px;font-weight:600;color:var(--txt3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:5px}
.inf-val{font-size:13.5px;color:var(--txt);font-weight:500}

/* ════════════════════════════════════════════
   AMOUNT COLOURS
════════════════════════════════════════════ */
.am-cell{font-variant-numeric:tabular-nums;font-weight:500}
.am-zero{color:var(--green)}
.am-part{color:var(--ylw)}
.am-full{color:var(--red)}

/* ════════════════════════════════════════════
   SPINNER
════════════════════════════════════════════ */
.spin{
  width:14px;height:14px;flex-shrink:0;
  border:2px solid rgba(0,0,0,.15);border-top-color:currentColor;
  border-radius:50%;animation:sp .7s linear infinite;
}
[data-theme="dark"] .spin{border-color:rgba(255,255,255,.15);border-top-color:currentColor}
@keyframes sp{to{transform:rotate(360deg)}}

/* ════════════════════════════════════════════
   TWO-COLUMN GRIDS
════════════════════════════════════════════ */
.g2{display:grid;grid-template-columns:1fr 320px;gap:18px;align-items:start}
.g2-form{display:grid;grid-template-columns:1fr 340px;gap:18px;align-items:start}

/* ════════════════════════════════════════════
   SIDEBAR OVERLAY
════════════════════════════════════════════ */
.sb-ov{
  display:none;position:fixed;inset:0;
  background:rgba(0,0,0,.5);z-index:150;
  -webkit-tap-highlight-color:transparent;
}
.sb-ov.show{display:block}

/* ════════════════════════════════════════════
   BOTTOM NAV (mobile)
════════════════════════════════════════════ */
.bottom-nav{
  display:none;
  position:fixed;bottom:0;left:0;right:0;
  height:var(--bnh);
  background:var(--bg2);border-top:1px solid var(--br);
  z-index:190;
  align-items:stretch;
  padding:0 4px;
  safe-area-inset-bottom:env(safe-area-inset-bottom,0);
  padding-bottom:env(safe-area-inset-bottom,0);
}
.bn{
  flex:1;display:flex;flex-direction:column;
  align-items:center;justify-content:center;
  gap:3px;padding:6px 4px;border-radius:var(--rs);
  color:var(--txt3);font-size:10px;font-weight:500;
  transition:color .15s,background .15s;cursor:pointer;
}
.bn:hover,.bn.active{color:var(--acc);background:var(--acc-bg)}
.bn svg{width:20px;height:20px;flex-shrink:0}
.bn span{line-height:1}

/* ════════════════════════════════════════════
   RESPONSIVE
════════════════════════════════════════════ */
@media(max-width:1280px){
  .sg{grid-template-columns:repeat(2,1fr)}
  .g2{grid-template-columns:1fr 290px}
  .g2-form{grid-template-columns:1fr 300px}
}
@media(max-width:1024px){
  .g2,.g2-form{grid-template-columns:1fr}
}
@media(max-width:768px){
  .sb{transform:translateX(-100%)}
  .sb.open{transform:translateX(0)}
  .main{margin-left:0}
  .menu-btn{display:flex}
  .pg{padding:16px 14px}
  .hdr{padding:0 12px}
  .fgrid{grid-template-columns:1fr}
  .inf-item+.inf-item{
    border-left:none;padding-left:0;margin-left:0;
    border-top:1px solid var(--br);padding-top:12px;
  }
  .sg{grid-template-columns:repeat(2,1fr);gap:10px}
  .fbar{gap:6px}
  .fbar .f-input{width:100%}
}
@media(max-width:640px){
  .bottom-nav{display:flex}
  .main{margin-bottom:var(--bnh)}
  .tw-c{bottom:calc(var(--bnh) + 10px)}
  .sg{grid-template-columns:repeat(2,1fr)}
  .hdr-acts .btn-p span{display:none} /* hide "New Receipt" text, keep icon */
  .ph h1{font-size:14px}
}
@media(max-width:420px){
  .sg{grid-template-columns:1fr}
  .fbar{flex-direction:column;align-items:stretch}
  .sbox{min-width:unset}
  .sc-val{font-size:18px}
  .card-body{padding:14px}
  .card-hdr{padding:12px 14px}
  .pg{padding:12px 10px}
}
</style>
</head>
<body>

<div class="sb-ov" id="sbOv" onclick="closeSb()"></div>

<div class="app">

  <!-- ══════ SIDEBAR ══════ -->
  <aside class="sb" id="sb">
    <div class="sb-logo">
      <div class="logo-ic" id="logoIc">
        <img
          src="receipts/static/logo.png"
          alt="ForgedCore"
          id="logoImg"
          onerror="this.style.display='none';document.getElementById('logoIc').classList.add('no-img');document.getElementById('logoFallback').style.display='flex'"
        >
        <span id="logoFallback" style="display:none;font-weight:800;font-size:12.5px;color:#000;letter-spacing:.5px">FC</span>
      </div>
      <div class="logo-txt">
        <span class="logo-n">ForgedCore</span>
        <span class="logo-s">Engineering Ltd</span>
      </div>
    </div>

    <nav class="sb-nav" aria-label="Main navigation">

      <!-- Overview Section -->
      <div class="nav-acc" id="acc-overview">
        <button class="nav-acc-btn" onclick="toggleAcc('acc-overview')" aria-expanded="true">
          Overview
          <svg class="chv" viewBox="0 0 24 24" fill="currentColor"><path d="M7 10l5 5 5-5z"/></svg>
        </button>
        <div class="nav-items">
          <a href="index.php" class="ni <?= $current_page==='index.php'?'active':'' ?>">
            <svg class="ni-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
            Dashboard
          </a>
        </div>
      </div>

      <!-- Receipts Section -->
      <div class="nav-acc" id="acc-receipts">
        <button class="nav-acc-btn" onclick="toggleAcc('acc-receipts')" aria-expanded="true">
          Receipts
          <svg class="chv" viewBox="0 0 24 24" fill="currentColor"><path d="M7 10l5 5 5-5z"/></svg>
        </button>
        <div class="nav-items">
          <a href="new-receipt.php" class="ni <?= $current_page==='new-receipt.php'?'active':'' ?>">
            <svg class="ni-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm-1 7V3.5L18.5 9H13zm-2 8v-2H8v-2h3v-3h2v3h3v2h-3v2h-2z"/></svg>
            New Receipt
          </a>
          <a href="clients.php" class="ni <?= in_array($current_page,['clients.php','view-receipt.php'])?'active':'' ?>">
            <svg class="ni-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 14H4V6h16v12zM6 10h2v2H6zm0 4h8v2H6zm10 0h2v2h-2zm-6-4h8v2h-8z"/></svg>
            All Receipts
          </a>
        </div>
      </div>

    </nav>

    <div class="sb-foot">
      <div class="sb-foot-txt">
        <strong>ForgedCore Engineering Ltd</strong><br>
        Kpobiman (Amasaman), Accra<br>
        0540202096 · 0545286665
      </div>
    </div>
  </aside>

  <!-- ══════ MAIN ══════ -->
  <div class="main">

    <header class="hdr">
      <button class="menu-btn" id="menuBtn" onclick="toggleSb()" aria-label="Toggle menu">
        <svg viewBox="0 0 24 24" fill="currentColor" width="22" height="22"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>
      </button>

      <div class="ph">
        <h1><?= htmlspecialchars($page_title??'') ?></h1>
        <?php if(!empty($page_subtitle)):?><p><?= htmlspecialchars($page_subtitle) ?></p><?php endif;?>
      </div>

      <div class="hdr-acts">
        <!-- Theme Toggle -->
        <button class="theme-btn" id="themeBtn" onclick="toggleTheme()" aria-label="Toggle light/dark mode" title="Toggle theme">
          <!-- Moon (shown in dark mode) -->
          <svg class="icon-moon" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 3a9 9 0 1 0 9 9c0-.46-.04-.92-.1-1.36a5.389 5.389 0 0 1-4.4 2.26 5.403 5.403 0 0 1-3.14-9.8c-.44-.06-.9-.1-1.36-.1z"/>
          </svg>
          <!-- Sun (shown in light mode) -->
          <svg class="icon-sun" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 7a5 5 0 1 0 0 10A5 5 0 0 0 12 7zM2 13h2a1 1 0 0 0 0-2H2a1 1 0 0 0 0 2zm18 0h2a1 1 0 0 0 0-2h-2a1 1 0 0 0 0 2zM11 2v2a1 1 0 0 0 2 0V2a1 1 0 0 0-2 0zm0 18v2a1 1 0 0 0 2 0v-2a1 1 0 0 0-2 0zM5.99 4.58a1 1 0 0 0-1.41 1.41l1.06 1.06a1 1 0 0 0 1.41-1.41L5.99 4.58zm12.37 12.37a1 1 0 0 0-1.41 1.41l1.06 1.06a1 1 0 0 0 1.41-1.41l-1.06-1.06zm1.06-10.96a1 1 0 0 0-1.41-1.41l-1.06 1.06a1 1 0 0 0 1.41 1.41l1.06-1.06zM7.05 18.36a1 1 0 0 0-1.41-1.41l-1.06 1.06a1 1 0 0 0 1.41 1.41l1.06-1.06z"/>
          </svg>
        </button>

        <?php if($current_page!=='new-receipt.php'):?>
        <a href="new-receipt.php" class="btn btn-p" id="newReceiptBtn">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
          <span>New Receipt</span>
        </a>
        <?php endif;?>
      </div>
    </header>

    <div class="pg">
<!-- page content injected here -->
