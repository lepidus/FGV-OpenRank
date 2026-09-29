import {resolve} from 'path';
import {defineConfig} from 'vite';
import vue from '@vitejs/plugin-vue';
import i18nExtractKeys from './i18nExtractKeys.vite.js';

export default defineConfig({
	plugins: [i18nExtractKeys(), vue()],
	publicDir: false,
	build: {
		target: 'es2016',
		lib: {
			entry: resolve(import.meta.dirname, 'resources/js/main.js'),
			name: 'RankingPlugin',
			fileName: 'build',
			formats: ['iife'],
		},
		outDir: resolve(import.meta.dirname, 'public/build'),
		rollupOptions: {
			external: ['vue'],
			output: {
				globals: {
					vue: 'pkp.modules.vue',
				},
			},
		},
	},
});
