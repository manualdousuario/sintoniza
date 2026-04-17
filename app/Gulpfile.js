"use strict";

// Load plugins
const gulp        = require("gulp");
const plumber     = require("gulp-plumber");
const sass        = require('gulp-sass')(require('sass'));
const concat      = require("gulp-concat");
const terser      = require('gulp-terser');
const sourcemaps  = require("gulp-sourcemaps");
const clean_css   = require('gulp-clean-css');

// Commons
function css_commons() {
	return gulp.src([
		"./node_modules/bootstrap/dist/css/bootstrap.min.css",
		"./source/scss/**/*.scss",
	])
		.pipe(sourcemaps.init())
		.pipe(sass({
			outputStyle: "expanded",
			includePaths: ['./node_modules']
		}))
		.pipe(concat('styles.css'))
		.pipe(clean_css())
		.pipe(sourcemaps.write('.'))
		.pipe(gulp.dest("./public/assets/css/"))
}

// Fonts task
function copy_fonts() {
	return gulp.src("./node_modules/bootstrap-icons/font/fonts/*", { encoding: false })
		.pipe(gulp.dest("./public/assets/fonts/"))
}

// JS task
function js_scripts() {
	return gulp
		.src([
			"./node_modules/bootstrap/dist/js/bootstrap.bundle.min.js",
			"./node_modules/howler/dist/howler.min.js",
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
		["./source/scss/**/*.scss"],
		{ usePolling: true },
		gulp.parallel(css_commons, css_home, css_admin, css_dashboard, css_auth)
	);

	gulp.watch(
		["./source/js/*.js"],
		{ usePolling: true },
		gulp.parallel(js_scripts)
	);
}

const css   = gulp.series(gulp.parallel(css_commons));
const fonts = gulp.series(copy_fonts);
const js    = gulp.series(js_scripts);
const build = gulp.series(gulp.parallel(css, fonts, js));
const watch = gulp.parallel(watchFiles);

exports.css   = css;
exports.fonts = fonts;
exports.js    = js;
exports.build = build;
exports.watch = watch;
exports.default = build;
