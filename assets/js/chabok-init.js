(function() {
  const auth = {
    appId: chabok_options.app_id,
    webKey: chabok_options.web_key,
    devMode: chabok_options.env === "dev"
  };

  const options = {
    webpush: {
      enabled: chabok_options.webpush === "on",
      publicKey: chabok_options.vapid || null
    }
  };

  window.chabok = new chabokpush.Chabok(auth, options);
})();
