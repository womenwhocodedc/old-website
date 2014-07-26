var gulp = require('gulp');

gulp.task('copy', function() {
	return gulp.src('public/**')
		.pipe(gulp.dest('build'));
});