import type FileUpload from "./file-upload/file-upload";

const fileUpload = {
  import: (): Promise<typeof FileUpload> => new Promise(resolve => import(
      /* webpackChunkName: "file-upload" */
      './file-upload/file-upload'
      ).then(({ default: FileUpload }) => resolve(FileUpload)
  )),
  initFields(): void {
    const enhancedFileUploads = document.querySelectorAll<HTMLElement>('.enhanced-file-upload');
    if (enhancedFileUploads.length) {
      this.import().then(FileUpload => enhancedFileUploads.forEach(element => FileUpload.instance(element)))
    }
  }
};

(window as any).enhancedfields = {
  fileUpload
}

document.addEventListener('DOMContentLoaded', () => (window as any).enhancedfields.fileUpload.initFields())
