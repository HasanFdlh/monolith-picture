<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'Photo Booth Dashboard')</title> <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">
  <!-- Google Font -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <!-- Custom CSS -->
  <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>

<body>
  <div class="wrapper">
    @include('partials.sidebar')

    <div class="main">
      @include('partials.navbar')

      <main class="content">
        @yield('content')
      </main>

    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {

      document.querySelectorAll('.frame-status-toggle').forEach(function(toggle) {

        toggle.addEventListener('change', async function() {

          const checkbox = this;
          const url = checkbox.dataset.url;
          const toggleText = checkbox
            .closest('.toggle-wrap')
            .querySelector('.toggle-text');

          // Simpan kondisi sebelum request
          const previousState = !checkbox.checked;

          checkbox.disabled = true;

          try {

            const response = await fetch(url, {
              method: 'PATCH',

              headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
              },

              body: JSON.stringify({
                is_active: checkbox.checked
              })
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
              throw new Error(
                data.message || 'Failed to update status'
              );
            }

            // Update text
            toggleText.textContent = data.is_active ?
              'Active' :
              'Inactive';

          } catch (error) {

            console.error(error);

            // Rollback checkbox
            checkbox.checked = previousState;

            alert('Failed to update frame status.');

          } finally {

            checkbox.disabled = false;
          }
        });

      });

    });
  </script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {

      const dropzone = document.getElementById('uploadDropzone');
      const fileInput = document.getElementById('frameFile');

      const uploadTitle = document.getElementById('uploadTitle');
      const uploadSubtitle = document.getElementById('uploadSubtitle');

      const selectedFile = document.getElementById('selectedFile');
      const selectedFileName = document.getElementById('selectedFileName');
      const selectedFileSize = document.getElementById('selectedFileSize');

      const removeFile = document.getElementById('removeFile');


      /*
      |--------------------------------------------------------------------------
      | Click Browse
      |--------------------------------------------------------------------------
      */

      fileInput.addEventListener('change', function() {

        if (this.files && this.files.length > 0) {
          handleFile(this.files[0]);
        }

      });


      /*
      |--------------------------------------------------------------------------
      | Drag Over
      |--------------------------------------------------------------------------
      */

      dropzone.addEventListener('dragover', function(e) {
        e.preventDefault();

        dropzone.classList.add('drag-over');
      });


      /*
      |--------------------------------------------------------------------------
      | Drag Leave
      |--------------------------------------------------------------------------
      */

      dropzone.addEventListener('dragleave', function(e) {
        e.preventDefault();

        // Jangan langsung remove ketika pindah ke child element
        if (!dropzone.contains(e.relatedTarget)) {
          dropzone.classList.remove('drag-over');
        }
      });


      /*
      |--------------------------------------------------------------------------
      | Drop
      |--------------------------------------------------------------------------
      */

      dropzone.addEventListener('drop', function(e) {
        e.preventDefault();

        dropzone.classList.remove('drag-over');

        const files = e.dataTransfer.files;

        if (!files || files.length === 0) {
          return;
        }

        const file = files[0];

        // Set file ke input
        const dataTransfer = new DataTransfer();

        dataTransfer.items.add(file);

        fileInput.files = dataTransfer.files;

        handleFile(file);
      });


      /*
      |--------------------------------------------------------------------------
      | Handle File
      |--------------------------------------------------------------------------
      */

      function handleFile(file) {

        const allowedTypes = [
          'image/png',
          'image/jpeg',
          'image/webp'
        ];

        if (!allowedTypes.includes(file.type)) {

          alert('Please select a PNG, JPG, JPEG or WEBP image.');

          resetFile();

          return;
        }


        /*
        |--------------------------------------------------------------------------
        | File Size
        |--------------------------------------------------------------------------
        */

        const fileSize = formatFileSize(file.size);


        /*
        |--------------------------------------------------------------------------
        | Update UI
        |--------------------------------------------------------------------------
        */

        selectedFileName.textContent = file.name;
        selectedFileSize.textContent = fileSize;

        selectedFile.classList.add('show');

        uploadTitle.textContent = 'Image selected';
        uploadSubtitle.textContent = 'Your image is ready to upload.';


        /*
        |--------------------------------------------------------------------------
        | Update Dropzone
        |--------------------------------------------------------------------------
        */

        dropzone.classList.add('has-file');
      }


      /*
      |--------------------------------------------------------------------------
      | Remove File
      |--------------------------------------------------------------------------
      */

      removeFile.addEventListener('click', function(e) {

        e.preventDefault();
        e.stopPropagation();

        resetFile();
      });


      /*
      |--------------------------------------------------------------------------
      | Reset
      |--------------------------------------------------------------------------
      */

      function resetFile() {

        fileInput.value = '';

        selectedFileName.textContent = '';
        selectedFileSize.textContent = '';

        selectedFile.classList.remove('show');

        uploadTitle.textContent =
          'Drop an image here or click to choose';

        uploadSubtitle.textContent =
          'PNG, JPG, JPEG or WEBP. Portrait and landscape frames are supported.';

        dropzone.classList.remove('has-file');
        dropzone.classList.remove('drag-over');
      }


      /*
      |--------------------------------------------------------------------------
      | Format File Size
      |--------------------------------------------------------------------------
      */

      function formatFileSize(bytes) {

        if (bytes === 0) {
          return '0 Bytes';
        }

        const units = [
          'Bytes',
          'KB',
          'MB',
          'GB'
        ];

        const index = Math.floor(
          Math.log(bytes) / Math.log(1024)
        );

        return (
          parseFloat(
            (bytes / Math.pow(1024, index)).toFixed(2)
          ) +
          ' ' +
          units[index]
        );
      }

    });
  </script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {

      const searchInput = document.getElementById('frameSearch');
      const clearSearch = document.getElementById('clearSearch');
      const resultsText = document.getElementById('resultsText');
      const searchEmptyState = document.getElementById('searchEmptyState');

      const frameCards = Array.from(
        document.querySelectorAll('.frame-card[data-search]')
      );

      const totalFrames = frameCards.length;


      function searchFrames() {

        const keyword = searchInput.value
          .trim()
          .toLowerCase();

        let visibleCount = 0;

        frameCards.forEach(function(card) {

          const searchableText = card.dataset.search || '';

          const isMatch =
            keyword === '' ||
            searchableText.includes(keyword);

          if (isMatch) {

            card.style.display = '';

            visibleCount++;

          } else {

            card.style.display = 'none';

          }
        });


        /*
        |--------------------------------------------------------------------------
        | Result text
        |--------------------------------------------------------------------------
        */

        if (keyword === '') {

          resultsText.textContent =
            `${totalFrames} matching frames of ${totalFrames} total`;

        } else {

          resultsText.textContent =
            `${visibleCount} matching frames of ${totalFrames} total`;
        }


        /*
        |--------------------------------------------------------------------------
        | Empty state
        |--------------------------------------------------------------------------
        */

        if (visibleCount === 0 && keyword !== '') {

          searchEmptyState.classList.add('show');

        } else {

          searchEmptyState.classList.remove('show');
        }


        /*
        |--------------------------------------------------------------------------
        | Clear button
        |--------------------------------------------------------------------------
        */

        if (keyword !== '') {

          clearSearch.classList.add('show');

        } else {

          clearSearch.classList.remove('show');
        }
      }


      /*
      |--------------------------------------------------------------------------
      | Search
      |--------------------------------------------------------------------------
      */

      searchInput.addEventListener('input', searchFrames);


      /*
      |--------------------------------------------------------------------------
      | Clear
      |--------------------------------------------------------------------------
      */

      clearSearch.addEventListener('click', function() {

        searchInput.value = '';

        searchInput.focus();

        searchFrames();
      });

    });
  </script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {

      const dropzone = document.getElementById('uploadDropzone');
      const fileInput = document.getElementById('frameFile');

      const uploadTitle = document.getElementById('uploadTitle');
      const uploadSubtitle = document.getElementById('uploadSubtitle');

      const selectedFile = document.getElementById('selectedFile');
      const selectedFileName = document.getElementById('selectedFileName');
      const selectedFileSize = document.getElementById('selectedFileSize');

      const removeFile = document.getElementById('removeFile');

      const advancedMetadata = document.getElementById('advancedMetadata');

      const advancedToggle = document.getElementById('advancedToggle');
      const advancedContent = document.getElementById('advancedContent');


      /*
      |--------------------------------------------------------------------------
      | File Input
      |--------------------------------------------------------------------------
      */

      fileInput.addEventListener('change', function() {

        if (this.files && this.files.length > 0) {
          handleFile(this.files[0]);
        }

      });


      /*
      |--------------------------------------------------------------------------
      | Drag Over
      |--------------------------------------------------------------------------
      */

      dropzone.addEventListener('dragover', function(e) {

        e.preventDefault();

        dropzone.classList.add('drag-over');

      });


      /*
      |--------------------------------------------------------------------------
      | Drag Leave
      |--------------------------------------------------------------------------
      */

      dropzone.addEventListener('dragleave', function(e) {

        e.preventDefault();

        if (!dropzone.contains(e.relatedTarget)) {
          dropzone.classList.remove('drag-over');
        }

      });


      /*
      |--------------------------------------------------------------------------
      | Drop
      |--------------------------------------------------------------------------
      */

      dropzone.addEventListener('drop', function(e) {

        e.preventDefault();

        dropzone.classList.remove('drag-over');

        const files = e.dataTransfer.files;

        if (!files || files.length === 0) {
          return;
        }

        const file = files[0];

        const dataTransfer = new DataTransfer();

        dataTransfer.items.add(file);

        fileInput.files = dataTransfer.files;

        handleFile(file);

      });


      /*
      |--------------------------------------------------------------------------
      | Handle File
      |--------------------------------------------------------------------------
      */

      function handleFile(file) {

        const allowedTypes = [
          'image/png',
          'image/jpeg',
          'image/webp'
        ];

        if (!allowedTypes.includes(file.type)) {

          alert(
            'Please select a PNG, JPG, JPEG or WEBP image.'
          );

          resetFile();

          return;
        }


        /*
        |--------------------------------------------------------------------------
        | File Information
        |--------------------------------------------------------------------------
        */

        selectedFileName.textContent = file.name;

        selectedFileSize.textContent =
          formatFileSize(file.size) + ' selected.';


        /*
        |--------------------------------------------------------------------------
        | Update Dropzone
        |--------------------------------------------------------------------------
        */

        selectedFile.classList.add('show');

        dropzone.classList.add('has-file');


        /*
        |--------------------------------------------------------------------------
        | Update Text
        |--------------------------------------------------------------------------
        */

        uploadTitle.textContent = file.name;

        uploadSubtitle.textContent =
          formatFileSize(file.size) +
          ' selected. Review advanced metadata before uploading.';


        /*
        |--------------------------------------------------------------------------
        | SHOW ADVANCED METADATA
        |--------------------------------------------------------------------------
        */

        advancedMetadata.classList.add('show');

      }


      /*
      |--------------------------------------------------------------------------
      | Remove File
      |--------------------------------------------------------------------------
      */

      removeFile.addEventListener('click', function(e) {

        e.preventDefault();
        e.stopPropagation();

        resetFile();

      });


      /*
      |--------------------------------------------------------------------------
      | Reset File
      |--------------------------------------------------------------------------
      */

      function resetFile() {

        fileInput.value = '';

        selectedFileName.textContent = '';

        selectedFileSize.textContent = '';

        selectedFile.classList.remove('show');

        dropzone.classList.remove('has-file');
        dropzone.classList.remove('drag-over');


        uploadTitle.textContent =
          'Drop an image here or click to choose';


        uploadSubtitle.textContent =
          'PNG, JPG, JPEG or WEBP. Portrait and landscape frames are supported.';


        /*
        |--------------------------------------------------------------------------
        | Hide Metadata
        |--------------------------------------------------------------------------
        */

        advancedMetadata.classList.remove('show');

      }


      /*
      |--------------------------------------------------------------------------
      | Advanced Metadata Toggle
      |--------------------------------------------------------------------------
      */

      advancedToggle.addEventListener('click', function() {

        advancedMetadata.classList.toggle('collapsed');

      });


      /*
      |--------------------------------------------------------------------------
      | Format File Size
      |--------------------------------------------------------------------------
      */

      function formatFileSize(bytes) {

        if (bytes === 0) {
          return '0 Bytes';
        }

        const units = [
          'Bytes',
          'KB',
          'MB',
          'GB'
        ];

        const index = Math.floor(
          Math.log(bytes) / Math.log(1024)
        );

        return (
          parseFloat(
            (bytes / Math.pow(1024, index)).toFixed(2)
          ) +
          ' ' +
          units[index]
        );

      }


      /*
      |--------------------------------------------------------------------------
      | Entire Dropzone Click
      |--------------------------------------------------------------------------
      */

      dropzone.addEventListener('click', function(e) {

        if (
          e.target.closest('.browse-btn') ||
          e.target.closest('.remove-file')
        ) {
          return;
        }

        fileInput.click();

      });

    });
  </script>
</body>

</html>
