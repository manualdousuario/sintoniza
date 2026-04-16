"use strict";

// Load plugins
const gulp = require("gulp");
const plumber = require("gulp-plumber");
const sass = require('gulp-sass')(require('sass'));
const concat = require("gulp-concat");
const terser = require('gulp-terser');
const sourcemaps = require("gulp-sourcemaps");
const clean_css = require('gulp-clean-css');

// CSS task
function css_styles() {
	return gulp.src("./source/scss/styles.scss")
		.pipe(sourcemaps.init())
		.pipe(sass({
			outputStyle: "expanded",
			includePaths: ['./node_modules']
		}))
		.pipe(concat('styles.css'))
		.pipe(clean_css())
		.pipe(gulp.dest("./public/assets/css/"))
		.pipe(sourcemaps.write('.'))
		.pipe(gulp.dest("./public/assets/css/"))
}

function js_scripts() {
	return gulp
		.src([
			"./source/js/scripts.js",
		])
		.pipe(sourcemaps.init())
		.pipe(plumber())
		.pipe(concat("./scripts.js"))
		.pipe(terser())
		.pipe(sourcemaps.write("./"))
		.pipe(gulp.dest("./public/assets/js/"));
}

// Watch files
function watchFiles() {
	gulp.watch(
		[
			"./source/scss/*.scss"
		],
		{ usePolling: true },
		gulp.parallel(css_styles)
	);
	
	gulp.watch(
		[
			"./source/js/*.js",
		],
		{ usePolling: true },
		gulp.parallel(js_scripts)
	);
}

// Define complex tasks
const css = gulp.series(
	css_styles
);
const js = gulp.series(
	js_scripts
);

const build = gulp.series(gulp.parallel(css, js));
const watch = gulp.parallel(watchFiles);

// Export Tasks
exports.css = css;
exports.js = js;
exports.build = build;
exports.watch = watch;
exports.default = build;
