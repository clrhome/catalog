catalog:
	convert -page 350x57 -background none -layers flatten src/bar.xcf bar.png
	tsc --strict src/index.d.ts src/catalog-edit.ts --outFile catalog-edit.js
	postcss src/catalog.css --use autoprefixer > catalog.css
	tsc --strict src/index.d.ts src/catalog.ts --outFile catalog.js

clean:
	rm -rf bar.png catalog-edit.js catalog.css catalog.js
