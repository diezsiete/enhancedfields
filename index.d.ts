declare global {
  interface Window {
    enhancedfields: {
      fileUpload: {
        import(): Promise<typeof FileUpload>;
        initFields(): void;
      }
    };
  }

  class FileUpload {
    static instance(element: HTMLElement): FileUpload;
  }
}

export {};
