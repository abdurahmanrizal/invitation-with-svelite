/// <reference types="@sveltejs/kit" />

interface ImportMetaEnv {
	readonly PUBLIC_PHP_API_URL: string;
}

interface ImportMeta {
	readonly env: ImportMetaEnv;
}
