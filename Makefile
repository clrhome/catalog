all: catalog-edit.js catalog.js catalog.css bar.png

catalog-edit.js: src/catalog-edit.ts
	tsc --strict src/index.d.ts src/catalog-edit.ts --outFile catalog-edit.js

catalog.js: src/catalog.ts
	tsc --strict src/index.d.ts src/catalog.ts --outFile catalog.js

catalog.css: src/catalog.css
	postcss src/catalog.css --use autoprefixer > catalog.css

bar.png: src/bar.xcf
	convert -page 350x57 -background none -layers flatten src/bar.xcf bar.png

clean:
	rm -rf bar.png catalog-edit.js catalog.css catalog.js
