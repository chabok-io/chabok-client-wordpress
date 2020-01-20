(function() {
  const auth = {
    appId: chabok_params.options.app_id,
    webKey: chabok_params.options.web_key,
    devMode: chabok_params.options.env === "dev"
  };

  const options = {
    webpush: {
      enabled: chabok_params.options.webpush === "on",
      publicKey: chabok_params.options.vapid || null
    },
    serviceWorker: {
      path: ,
      scope: '/',
    }
  };

  window.chabok = new chabokpush.Chabok(auth, options);
})();
