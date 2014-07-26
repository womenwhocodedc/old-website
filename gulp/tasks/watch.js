var gulp = require('gulp');

gulp.task('watch', ['setWatch', 'browserSync'], function() {
	gulp.watch('public/sass/**', ['compass']);
	gulp.watch('public/images/**', ['images']);
	// Note: The browserify task handles js recompiling with watchify
});
