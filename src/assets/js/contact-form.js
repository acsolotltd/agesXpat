function contactForm() {
  return {

    inquiry: 'consult_expert',

    submitting: false,

    message: '',

    fileName: '',

    csrfReady: false,


    services: [
      {
        value: 'IT Consultancy Services',
        label: 'IT Consultancy Services'
      },
      {
        value: 'Software Design & Dev',
        label: 'Software Design & Dev'
      },
      {
        value: 'CCTV & Security Installs',
        label: 'CCTV & Security Installs'
      },
      {
        value: 'Corporate System Mgmt',
        label: 'Corporate System Mgmt'
      },
      {
        value: 'Technical Training',
        label: 'Technical Training'
      }
    ],


    async init() {

      await this.loadCsrf();

    },


    async loadCsrf() {

      try {

        const response =
          await fetch('/get-csrf-token.php', {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
              'Accept': 'application/json'
            }
          });


        if (!response.ok) {
          throw new Error(
            'Unable to initialize secure form.'
          );
        }


        const data =
          await response.json();


        const csrfInput =
          document.getElementById('csrf-token');


        if (!csrfInput || !data.csrf_token) {
          throw new Error(
            'Security token unavailable.'
          );
        }


        csrfInput.value =
          data.csrf_token;


        this.csrfReady = true;

      }

      catch (error) {

        console.error(error);

        this.csrfReady = false;

        const response =
          document.getElementById('form-response');


        if (response) {

          response.innerHTML = `
            <div class="
              rounded-xl
              border border-red-200
              bg-red-50
              dark:bg-red-950/30
              dark:border-red-900
              p-4
              text-sm
              text-red-700
              dark:text-red-300
            ">
              Unable to initialize the secure contact form.
              Please refresh the page and try again.
            </div>
          `;

        }

      }

    },


    handleFile(event) {

      const file =
        event.target.files[0];


      if (!file) {

        this.fileName = '';

        return;

      }


      if (file.size > 10 * 1024 * 1024) {

        this.fileName = '';

        event.target.value = '';

        const response =
          document.getElementById('form-response');


        if (response) {

          response.innerHTML = `
            <div class="
              rounded-xl
              border border-red-200
              bg-red-50
              dark:bg-red-950/30
              dark:border-red-900
              p-4
              text-sm
              text-red-700
              dark:text-red-300
            ">
              The selected file exceeds the 10MB limit.
            </div>
          `;

        }

        return;

      }


      this.fileName =
        `${file.name} (${this.formatBytes(file.size)})`;

    },


    formatBytes(bytes) {

      if (bytes === 0) {
        return '0 Bytes';
      }


      const units = [
        'Bytes',
        'KB',
        'MB',
        'GB'
      ];


      const index =
        Math.floor(
          Math.log(bytes) / Math.log(1024)
        );


      return `${parseFloat(
        (bytes / Math.pow(1024, index))
          .toFixed(2)
      )} ${units[index]}`;

    }

  };
}
