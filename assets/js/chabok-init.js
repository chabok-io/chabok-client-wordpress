(function() {
  let isRegistered = false;

  function initialize() {
    if (! chabok_params) {
      throw new Error('Chabok Parameters are not loaded. Stopping Chabok scripts...');
    }
    const auth = {
      appId: chabok_params.options.app_id,
      webKey: chabok_params.options.web_key,
      devMode: chabok_params.options.env !== 'prod'
    };

    const options = {
      webpush: {
        enabled: chabok_params.options.webpush !== 'off',
        publicKey: chabok_params.options.vapid || null
      },
      realtime: chabok_params.options.realtime !== 'off'
    };

    window.chabok = new chabokpush.Chabok(auth, options);
  }


  function register() {
    if (chabok_params.options.register_users !== 'on') {
      return;
    }

    getUser().then(result => {
      result = JSON.parse(result);

      if (! result.user && result.logged_out) {
        chabok.logout();
        setLoggedOut();
        return;
      }

      const user = result.user;
      const deviceId = chabok.getInstallationId();
      const userId = chabok.getUserId();

      readDeviceFromSession().then(existingSession => {
        existingSession = JSON.parse(existingSession);

        if (existingSession.not_registered && user) {
          chabok.login(user);
          loggedIn();
        }
        if ((user && existingSession.not_registered) || ! existingSession.device_id || existingSession.device_id !== deviceId) {
          saveDeviceToSession({ deviceId, userId });
        }
      });
    });
  }

  function setLoggedOut() {
    return new Promise((resolve, reject) => {
      jQuery.ajax({
        url: chabok_params.xhr_endpoint,
        data: {
          action: 'chabok_logout',
        },
        success: resolve,
        error: reject,
      });
    });
  }

  function loggedIn() {
    return new Promise((resolve, reject) => {
      jQuery.ajax({
        url: chabok_params.xhr_endpoint,
        data: {
          action: 'chabok_logged_in',
        },
        success: resolve,
        error: reject,
      });
    });
  }

  function readDeviceFromSession() {
    return new Promise((resolve, reject) => {
      jQuery.ajax({
        url: chabok_params.xhr_endpoint,
        method: 'POST',
        data: {
          action: 'chabok_read_from_session',
        },
        success: resolve,
        error: reject,
      });
    });
  }

  function saveDeviceToSession(data) {
    return new Promise((resolve, reject) => {
      jQuery.ajax({
        url: chabok_params.xhr_endpoint,
        method: 'POST',
        data: {
          action: 'chabok_register_in_session',
          ...data,
        },
        success: resolve,
        error: reject,
      });
    });
  }

  function getUser() {
    return new Promise((resolve, reject) => {
      jQuery.ajax({
        url: chabok_params.xhr_endpoint,
        method: 'POST',
        data: {
          action: 'chabok_get_user',
        },
        success: resolve,
        error: reject,
      });
    });
  }

  initialize();
  register();
})();
