module.exports = function (grunt) {
    // Project configuration.
    grunt.initConfig({
        modx: grunt.file.readJSON('_build/config.json'),
        banner: '/*!\n' +
            ' * <%= modx.name %> - <%= modx.description %>\n' +
            ' * Version: <%= modx.version %>\n' +
            ' * Build date: <%= grunt.template.today("yyyy-mm-dd") %>\n' +
            ' */\n',
        usebanner: {
            css: {
                options: {
                    position: 'bottom',
                    banner: '<%= banner %>'
                },
                files: {
                    src: [
                        'assets/components/tailwindhelper/css/mgr/tailwindhelper.min.css',
                    ]
                }
            },
            js: {
                options: {
                    position: 'top',
                    banner: '<%= banner %>'
                },
                files: {
                    src: [
                        'assets/components/tailwindhelper/js/mgr/tailwindhelper.min.js',
                    ]
                }
            }
        },
        uglify: {
            mgr: {
                src: [
                    'source/js/mgr/tailwindhelper.js',
                    'source/js/mgr/helper/util.js',
                ],
                dest: 'assets/components/tailwindhelper/js/mgr/tailwindhelper.min.js'
            }
        },
        sass: {
            options: {
                implementation: require('node-sass'),
                outputStyle: 'expanded',
                sourcemap: false
            },
            mgr: {
                files: {
                    'source/css/mgr/tailwindhelper.css': 'source/sass/mgr/tailwindhelper.scss'
                }
            }
        },
        postcss: {
            options: {
                processors: [
                    require('pixrem')(),
                    require('autoprefixer')()
                ]
            },
            mgr: {
                src: [
                    'source/css/mgr/tailwindhelper.css'
                ]
            }
        },
        cssmin: {
            mgr: {
                src: [
                    'source/css/mgr/tailwindhelper.css'
                ],
                dest: 'assets/components/tailwindhelper/css/mgr/tailwindhelper.min.css'
            }
        },
        imagemin: {
            png: {
                options: {
                    optimizationLevel: 7
                },
                files: [
                    {
                        expand: true,
                        cwd: 'source/img/',
                        src: ['**/*.png'],
                        dest: 'assets/components/tailwindhelper/img/mgr/',
                        ext: '.png'
                    }
                ]
            },
            jpg: {
                options: {
                    progressive: true
                },
                files: [
                    {
                        expand: true,
                        cwd: 'source/img/',
                        src: ['**/*.jpg'],
                        dest: 'assets/components/tailwindhelper/img/mgr/',
                        ext: '.jpg'
                    }
                ]
            },
            gif: {
                files: [
                    {
                        expand: true,
                        cwd: 'source/img/',
                        src: ['**/*.gif'],
                        dest: 'assets/components/tailwindhelper/img/mgr/',
                        ext: '.gif'
                    }
                ]
            }
        },
        watch: {
            js: {
                files: [
                    'source/**/*.js'
                ],
                tasks: ['uglify', 'usebanner:js']
            },
            css: {
                files: [
                    'source/**/*.scss'
                ],
                tasks: ['sass', 'postcss', 'cssmin', 'usebanner:css']
            },
            config: {
                files: [
                    '_build/config.json'
                ],
                tasks: ['default']
            }
        },
        bump: {
            copyright: {
                files: [{
                    src: 'core/components/tailwindhelper/src/TailwindHelper.php',
                    dest: 'core/components/tailwindhelper/src/TailwindHelper.php'
                },{
                    src: 'core/components/tailwindhelper/model/tailwindhelper/tailwindhelper.class.php',
                    dest: 'core/components/tailwindhelper/model/tailwindhelper/tailwindhelper.class.php'
                }],
                options: {
                    replacements: [{
                        pattern: /Copyright \d{4}(-\d{4})? by/g,
                        replacement: 'Copyright ' + (new Date().getFullYear() > 2021 ? '2021-' : '') + new Date().getFullYear() + ' by'
                    }]
                }
            },
            version: {
                files: [{
                    src: 'core/components/tailwindhelper/src/TailwindHelper.php',
                    dest: 'core/components/tailwindhelper/src/TailwindHelper.php'
                }],
                options: {
                    replacements: [{
                        pattern: /version = '\d+.\d+.\d+[-a-z0-9]*'/ig,
                        replacement: 'version = \'' + '<%= modx.version %>' + '\''
                    }]
                }
            },
            homepanel: {
                files: [{
                    src: 'source/js/mgr/widgets/home.panel.js',
                    dest: 'source/js/mgr/widgets/home.panel.js'
                }],
                options: {
                    replacements: [{
                        pattern: /© \d{4}(-\d{4})? by/g,
                        replacement: '© ' + (new Date().getFullYear() > 2021 ? '2021-' : '') + new Date().getFullYear() + ' by'
                    }]
                }
            },
            docs: {
                files: [{
                    src: 'mkdocs.yml',
                    dest: 'mkdocs.yml'
                }],
                options: {
                    replacements: [{
                        pattern: /&copy; \d{4}(-\d{4})?/g,
                        replacement: '&copy; ' + (new Date().getFullYear() > 2021 ? '2021-' : '') + new Date().getFullYear()
                    }]
                }
            }
        }
    });

    //load the packages
    grunt.loadNpmTasks('@lodder/grunt-postcss');
    grunt.loadNpmTasks('grunt-banner');
    grunt.loadNpmTasks('grunt-contrib-cssmin');
    grunt.loadNpmTasks('grunt-contrib-imagemin');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-sass');
    grunt.loadNpmTasks('grunt-string-replace');
    grunt.renameTask('string-replace', 'bump');

    //register the task
    grunt.registerTask('default', ['bump', 'uglify', 'sass', 'postcss', 'cssmin', 'usebanner']);
};
