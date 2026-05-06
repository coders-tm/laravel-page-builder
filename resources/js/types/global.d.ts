import { Page, SectionSchema, SectionData } from "./page-builder";

declare global {
    interface Window {
        PageBuilder?: {
            pages?: Page[];
            sections?: Record<string, SectionData>;
            config?: {
                baseUrl: string;
                csrfToken: string;
            };
            baseUrl?: string;
            fieldTypes?: Record<
                string,
                (args: {
                    setting: any;
                    value: any;
                    onChange: (val: any) => void;
                    container: HTMLElement;
                }) => void | string | HTMLElement
            >;
        };
    }
}

export {};
