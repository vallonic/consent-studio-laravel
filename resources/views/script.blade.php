<script type="text/javascript">
   /***********************************************
    * Consent Studio - European CMP
    * 🇳🇱 Built in the Netherlands
    * 🇪🇺 100% European-owned infrastructure
    *
    * Questions? support@consent.studio
    * Documentation: https://learn.consent.studio
    ***********************************************/

   /** Configuration (customize as needed) */
   window.bakery = window.bakery || {
      googleConsentMode: {
         enabled: {{ $config['google_consent_mode']['enabled'] ? 'true' : 'false' }},
         wait_for_update: {{ $config['google_consent_mode']['wait_for_update'] }},
         ads_data_redaction: {{ $config['google_consent_mode']['ads_data_redaction'] ? 'true' : 'false' }},
         url_passthrough: {{ $config['google_consent_mode']['url_passthrough'] ? 'true' : 'false' }},
         defaults: {!! json_encode($config['google_consent_mode']['defaults']) !!}
      },
      debug: {{ $config['debug'] ? 'true' : 'false' }}
   };

   /** Consent Studio Implementation */
   (function(m,a,r,t,i,n){var g=window.bakery.googleConsentMode;
   if(g&&g.enabled){window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments)}
   m="granted";a="denied";r=location;i=g.defaults||{ad_storage:a,ad_user_data:a,ad_personalization:a,analytics_storage:a,functionality_storage:m,personalization_storage:m,security_storage:m};
   if(r.hash.substr(1,7)=="cs-scan"){gtag("consent","default",{ad_storage:m,ad_user_data:m,ad_personalization:m,analytics_storage:m,functionality_storage:m,personalization_storage:m,security_storage:m,wait_for_update:g.wait_for_update})}else{if(Array.isArray(i)){for(t=0;t<i.length;t++){n=Object.assign({},i[t],{wait_for_update:g.wait_for_update});gtag("consent","default",n)}}else{gtag("consent","default",Object.assign({},i,{wait_for_update:g.wait_for_update}))}}
   if(g.ads_data_redaction)gtag("set","ads_data_redaction",true);if(g.url_passthrough)gtag("set","url_passthrough",true);gtag("set","developer_id.dZTlmZj",true)}
   if(window.bakery._loaded)return;window.bakery._loaded=1;n=document.createElement('script');n.src='https://consent.studio/'+r.hostname+'/banner.js';document.head.appendChild(n)})();
</script>
