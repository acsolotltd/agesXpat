document.addEventListener('DOMContentLoaded', () => {

  const form = document.getElementById('contact-form');

  if (!form) {
    return;
  }


  /*
  |--------------------------------------------------------------------------
  | Before request
  |--------------------------------------------------------------------------
  */

  form.addEventListener(
    'htmx:beforeRequest',
    () => {

      const app =
        form.closest('[x-data]');

      if (
        app &&
        app.__x
      ) {
        // Alpine 3 manages this internally.
      }

    }
  );


  /*
  |--------------------------------------------------------------------------
  | Successful request
  |--------------------------------------------------------------------------
  */

  form.addEventListener(
    'htmx:afterRequest',
    (event) => {

      const successful =
        event.detail.successful;


      if (!successful) {
        return;
      }


      /*
      | Only reset when the server accepted
      | the request.
      */

      if (
        event.detail.xhr.status === 200
      ) {

        form.reset();


        /*
        | Reset file name display.
        */

        const fileName =
          form.querySelector(
            '[x-text*="fileName"]'
          );


        /*
        | Reload the page state only through
        | normal DOM state where necessary.
        */

        const csrf =
          document.getElementById(
            'csrf-token'
          );


        if (csrf) {
          csrf.value = '';
        }

      }

    }
  );


  /*
  |--------------------------------------------------------------------------
  | Network / server failure
  |--------------------------------------------------------------------------
  */

  form.addEventListener(
    'htmx:responseError',
    (event) => {

      const target =
        document.getElementById(
          'form-response'
        );


      if (
        target &&
        event.detail.xhr.responseText
      ) {

        target.innerHTML =
          event.detail.xhr.responseText;

      }

    }
  );

});
