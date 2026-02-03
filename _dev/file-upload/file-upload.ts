import './file-upload.scss'
import Dropzone from 'dropzone';
import FileUploadElements from "./file-upload-elements";

Dropzone.autoDiscover = false;

export default class FileUpload {

  private fetchUrl: string;
  private readonly uploadUrl: string;
  private readonly filenameOriginal: string;
  private readonly fileSizeLabel: string;
  private readonly placeholder: string;

  static instance(element: HTMLElement): FileUpload|null {
    const inputContainer = element.querySelector<HTMLElement>('.col-sm');
    const inputElement = element.querySelector<HTMLInputElement>('input.custom-file-input');
    const labelElement =  element.querySelector<HTMLLabelElement>('label.custom-file-label');

    return inputContainer && inputElement && labelElement
      ? new FileUpload(new FileUploadElements(element, inputContainer, inputElement, labelElement))
      : null;
  }

  constructor(
    private readonly elements: FileUploadElements
  ) {

    this.fetchUrl = this.elements.dataset('fetchUrl');
    this.uploadUrl = this.elements.dataset('uploadUrl');
    this.filenameOriginal = this.elements.dataset('filenameOriginal');
    this.fileSizeLabel = this.elements.dataset('fileSizeLabel','File size');
    this.placeholder = this.elements.dataset('placeholder', 'Choose file(s)')

    const dropzone = new Dropzone(this.elements.input, {
      url: this.uploadUrl,
      paramName: 'file_upload',
      sending: () => this.elements.loading(true),
      error: (file, response) => this.onDropzoneError(response),
    });
    dropzone.on('success', (file, response) => this.onDropzoneSuccess(response))

    if (this.elements.deletable) {
      this.elements.onDelete(() => this.deleteHandler())
    }
  }

  private onDropzoneError(response: string|any) {
    this.elements.loading(false);
    this.addErrorMessage(
      typeof response === 'string' ? response : (response.message || response.detail || response.title || 'Unknown error')
    );
  }

  private async onDropzoneSuccess(response: any) {
    this.elements.label.innerText = response.name
    this.elements.input.value = response.path
    await this.showImage(response.path);
    this.elements.loading(false);
  }

  private addErrorMessage(message: string) {
    this.elements.getTextDanger().innerText = ' ' + message.trim()
  }

  private async showImage(filename: string) {
    const url = this.fetchUrl.replace('location/filename', filename);
    const response = await fetch(url);

    const blob = await response.blob();
    const blobUrl = URL.createObjectURL(blob)
    this.elements.getImg().src = blobUrl;
    if (this.elements.deletable) {
      const contentLength = response.headers.get('Content-Length');
      this.setSize(contentLength ? parseInt(contentLength) : 0);
      this.elements.showDeleteButton();
    }

    return blobUrl;
  }

  private setSize(sizeInBytes: number) {
    if (sizeInBytes) {
      this.elements.getSize().innerText = `${this.fileSizeLabel} ${(sizeInBytes / 1024).toFixed(2)}kB`;
    } else {
      this.elements.removeSize();
    }
  }

  private async deleteHandler() {
    this.elements.label.innerText = this.placeholder
    this.elements.input.value = ''
    this.elements.removeFileViewer();
  }
}
