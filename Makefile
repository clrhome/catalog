catalog:
	tsc --strict src/index.d.ts src/catalog-edit.ts --outFile catalog-edit.js
	postcss src/catalog.css --use autoprefixer > catalog.css
	tsc --strict src/index.d.ts src/catalog.ts --outFile catalog.js

clean:
	rm -rf catalog-edit.js catalog.css catalog.js
