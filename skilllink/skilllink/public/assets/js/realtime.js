(function(){
  'use strict';
  let lastNotifId=parseInt(document.body.dataset.lastNotifId||'0',10);
  let lastJobId=parseInt(document.body.dataset.lastJobId||'0',10);
  const role=document.body.dataset.role||'';
  let es=null;

  function connect(){
    if(es)es.close();
    const url=(window.SL_BASE||'')+'realtime.php?last_notif_id='+lastNotifId+'&last_job_id='+lastJobId;
    es=new EventSource(url);
    es.addEventListener('connected',()=>showStatus('live'));
    es.addEventListener('notifications',function(e){
      var d=JSON.parse(e.data);
      updateBadge(d.unread);
      d.items.forEach(function(n){
        lastNotifId=Math.max(lastNotifId,n.id);
        prependNotif(n);
        if(!n.is_read)pulseBell();
      });
    });
    es.addEventListener('new_jobs',function(e){
      var d=JSON.parse(e.data);
      d.jobs.forEach(function(j){lastJobId=Math.max(lastJobId,j.id);prependJob(j);});
      bumpJobCount(d.jobs.length);
    });
    es.addEventListener('app_stats',function(e){
      var s=JSON.parse(e.data);
      setVal('stat-total',s.applied+s.accepted+s.rejected);
      setVal('stat-accepted',s.accepted);
      setVal('stat-pending',s.applied);
    });
    es.addEventListener('applicant_counts',function(e){
      JSON.parse(e.data).jobs.forEach(function(j){
        var b=document.querySelector('[data-job-id="'+j.id+'"] .app-count');
        if(b)countTo(b,parseInt(b.textContent,10),j.app_cnt);
      });
    });
    es.addEventListener('platform_stats',function(e){
      var s=JSON.parse(e.data);
      setVal('admin-students',s.students);setVal('admin-employers',s.employers);
      setVal('admin-jobs',s.jobs);setVal('admin-applications',s.applications);
    });
    es.addEventListener('reconnect',function(e){
      var d=JSON.parse(e.data);es.close();showStatus('reconnecting');
      setTimeout(connect,d.delay||3000);
    });
    es.onerror=function(){es.close();showStatus('reconnecting');setTimeout(connect,4000);};
  }

  function showStatus(s){
    var d=document.getElementById('sl-live-dot');
    if(!d)return;
    d.className='sl-live-dot sl-live-'+s;
    d.title=s==='live'?'Real-time connected':'Reconnecting\u2026';
  }
  function updateBadge(n){
    document.querySelectorAll('.sl-bell-badge').forEach(function(b){
      b.textContent=n;b.style.display=n>0?'':'none';
    });
  }
  function pulseBell(){
    var b=document.querySelector('.sl-bell-btn');
    if(!b)return;b.classList.add('sl-bell-pulse');
    setTimeout(function(){b.classList.remove('sl-bell-pulse');},800);
  }
  function prependNotif(n){
    var list=document.getElementById('sl-notif-list');if(!list)return;
    var div=document.createElement('div');
    div.className='sl-notif-item unread sl-rt-new';
    div.innerHTML='<span class="sl-notif-dot"></span><div style="flex:1;min-width:0"><p class="sl-notif-msg">'+esc(n.message)+'</p><p class="sl-notif-time">'+(n.time_ago||'just now')+'</p></div>';
    if(n.link){div.style.cursor='pointer';div.onclick=function(){location.href=n.link;};}
    list.prepend(div);
    while(list.children.length>10)list.lastChild.remove();
    var em=list.querySelector('.sl-notif-empty');if(em)em.remove();
  }
  function prependJob(job){
    var c=document.getElementById('sl-job-list');if(!c)return;
    var colors=['#3B82F6','#8B5CF6','#10B981','#F59E0B','#EF4444','#06B6D4'];
    var ini=(job.company_name||'C').charAt(0).toUpperCase();
    var col=colors[ini.charCodeAt(0)%colors.length];
    var skills=(job.required_skills||[]).slice(0,4).map(function(s){return'<span class="sl-tag">'+esc(s)+'</span>';}).join('');
    var base=window.SL_BASE||'../';
    var card=document.createElement('div');
    card.className='sl-job-card sl-rt-new';
    card.setAttribute('data-job-id',job.id);
    card.innerHTML='<div class="sl-job-logo" style="background:'+col+'">'+ini+'</div>'
      +'<div style="flex:1;min-width:0">'
      +'<div class="sl-job-title">'+esc(job.title)+'</div>'
      +'<div class="sl-job-meta">'+esc(job.company_name)+(job.location?' &middot; '+esc(job.location):'')+'</div>'
      +(skills?'<div style="display:flex;flex-wrap:wrap;gap:4px;margin-bottom:10px">'+skills+'</div>':'')
      +'<a href="'+base+'job_details.php?id='+job.id+'" class="sl-btn sl-btn-sm">View &amp; Apply &rarr;</a>'
      +'</div>';
    c.prepend(card);
    var em=c.querySelector('[data-empty]');if(em)em.remove();
  }
  function bumpJobCount(n){
    var el=document.getElementById('sl-job-count');if(!el)return;
    var v=(parseInt(el.textContent,10)||0)+n;
    el.textContent=v+' listing'+(v!==1?'s':'');
  }
  function setVal(id,val){
    var el=document.getElementById(id);if(!el)return;
    var cur=parseInt(el.textContent,10);
    if(!isNaN(cur)&&cur!==val)countTo(el,cur,val);
  }
  function countTo(el,from,to){
    to=parseInt(to,10);if(from===to)return;
    var steps=12,step=(to-from)/steps,cur=from,i=0;
    var iv=setInterval(function(){cur+=step;i++;
      el.textContent=Math.round(i<steps?cur:to);
      if(i>=steps)clearInterval(iv);
    },40);
  }
  function esc(s){
    return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  if(typeof EventSource!=='undefined')connect();
})();
