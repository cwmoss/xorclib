module.exports = function(grunt) {
  // Do grunt-related things in here

  // Project configuration.
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		uglify: {
			options: {
				banner: '/*! <%= pkg.name %> <%= grunt.template.today("yyyy-mm-dd") %> */\n'
			},
			build: {
				src: 'public/js/<%= pkg.name %>.js',
				dest: 'public/js/<%= pkg.name %>.ugl.js'
			}
		},
		concat: {
			options: {
				separator: ';\n',
			},
			dist: {
				src: [
					'node_modules/jquery/dist/jquery.min.js',
					//'node_modules/bootstrap-sass/assets/javascripts/bootstrap.min.js',
					'node_modules/@vimeo/player/dist/player.js',
					'public/js/<%= pkg.name %>.ugl.js'
					],
				dest: 'public/js/<%= pkg.name %>.min.js',
			},
		}
	});

	// Load the plugin that provides the "uglify" task.
	grunt.loadNpmTasks('grunt-contrib-uglify');

	grunt.loadNpmTasks('grunt-contrib-concat');


	// Default task(s).
	grunt.registerTask('default', ['uglify', 'concat']);

};