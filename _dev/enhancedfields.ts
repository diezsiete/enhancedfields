
document.addEventListener('DOMContentLoaded', () => {

  const enhancedFileUploads = document.querySelectorAll<HTMLElement>('.enhanced-file-upload');
  if (enhancedFileUploads.length) {
    import(
      /* webpackChunkName: "file-upload" */
      './file-upload/file-upload'
    ).then(
      ({ default: FileUpload }) => enhancedFileUploads.forEach(element => FileUpload.instance(element))
    );
  }


})
