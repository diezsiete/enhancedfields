import './file-upload.scss'
import Dropzone from 'dropzone';

Dropzone.autoDiscover = false;

export default class FileUpload {

  private fetchUrl: string;
  private uploadUrl: string;
  private filename: string;
  private filenameOriginal: string;

  static instance(element: HTMLElement): FileUpload|null {
    const inputContainer = element.querySelector<HTMLElement>('.col-sm');
    const inputElement = element.querySelector<HTMLInputElement>('input.custom-file-input');
    const labelElement =  element.querySelector<HTMLLabelElement>('label.custom-file-label');

    return inputContainer && inputElement && labelElement
      ? new FileUpload(element, inputContainer, inputElement, labelElement)
      : null;
  }

  constructor(
    private element: HTMLElement,
    private inputContainer: HTMLElement,
    private inputElement: HTMLInputElement,
    private labelElement: HTMLLabelElement
  ) {
    this.fetchUrl = element.dataset.fetchUrl + '';
    this.uploadUrl = element.dataset.uploadUrl + '';
    this.filename = element.dataset.filename + '';
    this.filenameOriginal = element.dataset.filenameOriginal + '';

    const dropzone = new Dropzone(this.inputElement, {
      url: this.uploadUrl,
      paramName: 'file_upload',
      error: (file, response) => this.onDropzoneError(response),
    });
    dropzone.on('success', (file, response) => this.onDropzoneSuccess(response))

    if (this.filenameOriginal) {
      this.showImage(this.filenameOriginal)
    }
  }

  private onDropzoneError(response: string|any) {
    this.addErrorMessage(
      typeof response === 'string' ? response : (response.message || response.detail || response.title || 'Unknown error')
    );
  }

  private onDropzoneSuccess(response: any) {
    this.labelElement.innerText = response.name
    this.inputElement.value = response.path
    this.showImage(response.path)
  }

  private addErrorMessage(message: string) {
    let textDanger = this.element.querySelector<HTMLElement>('.text-danger span')
    if (!textDanger) {
      const textDangerContainer = this.createElement('div', ['d-inline-block', 'align-baseline', 'text-danger', 'mt-1'], {'role': 'alert'});
      textDanger = this.createElement('span');

      textDangerContainer.appendChild(this.createElement('i', ['material-icons', 'form-error-icon'], 'error_outline'))
      textDangerContainer.appendChild(textDanger)
      this.inputContainer.appendChild(textDangerContainer)
    }
    textDanger.innerText = ' ' + message.trim()
  }

  private showImage(filename: string) {
    const url = this.fetchUrl.replace('location/filename', filename)
    this.fetch(url)
      .then(response => response.blob())
      .then(blob => {
        const blobUrl = URL.createObjectURL(blob)
        this.getFileViewerContainer(blobUrl)
      });
  }

  private fetch(url: string) {
    const headers = new Headers();
    // options.headers.set('Accept', 'application/json, text/javascript, */*; q=0.01');
    headers.set('X-Requested-With', 'XMLHttpRequest');
    return fetch(url, { headers })
  }

  private getFileViewerContainer(src: string) {
    let fileViewerContainer = this.element.querySelector<HTMLElement>('.file-viewer')
    if (!fileViewerContainer) {
      fileViewerContainer = this.createElement('div', ['form-group', 'row', 'file-viewer'])
      const label = this.createElement('label', 'form-control-label')
      const colSm = this.createElement('div', 'col-sm')

      const figure = this.createElement('figure', 'figure', this.createElement(
        'img', ['figure-img', 'img-fluid', 'img-thumbnail'], src
      ))
      colSm.appendChild(figure)
      fileViewerContainer.appendChild(label)
      fileViewerContainer.appendChild(colSm)
      this.element.appendChild(fileViewerContainer)
    } else {
      fileViewerContainer.querySelector<HTMLImageElement>('figure img')?.setAttribute('src', src);
    }
  }

  private createElement(tagName: string, className?: string|string[], inner?: string|HTMLElement|Record<string, string>): HTMLElement {
    const element = document.createElement(tagName)
    if (Array.isArray(className)) {
      if (className.length > 0) {
        element.classList.add(...className);
      }
    } else if (className) {
      element.classList.add(className)
    }
    if (inner) {
      if (tagName === 'img') {
        (element as HTMLImageElement).src = inner as string
      } else if (inner instanceof HTMLElement) {
        element.appendChild(inner)
      } else if (typeof inner === 'object') {
        for (const key in inner) {
          element.setAttribute(key, inner[key])
        }
      } else {
        element.innerText = inner
      }
    }
    return element
  }
}
