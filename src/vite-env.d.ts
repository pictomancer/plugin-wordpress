/// <reference types="vite/client" />

declare module '*.css?inline' {
  const css: string;
  export default css;
}

interface PictomancerData {
  restUrl: string;
  nonce: string;
}

interface Window {
  pictomancerData?: PictomancerData;
}
