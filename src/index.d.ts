interface Window {
  catalogActiveEntries: Array<HTMLAnchorElement>;
  catalogCancel: (event: Event) => void;
  catalogTokenUrl: (href: string) => string;
}
