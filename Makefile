catalog:
	postcss src/catalog.css --use autoprefixer > catalog.css

clean:
	rm -rf catalog.css
