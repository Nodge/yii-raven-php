yii-raven-php extension
=======================

This extension provide intergration with raven-php with the following features:

* Use Yii Error Handler to log php errors to Sentry server
* Use Yii Error Handler to log uncaught exceptions to Sentry server
* Ability to log exceptions manually (usually inside try-catch block)
* Configure raven through yii config files: tags, modules, user, etc

### todo

* optionally log 403 errors
* yii-user integration class

### Resources

* [yii-raven-php](https://github.com/Nodge/yii-raven-php)
* [Yii Framework](http://yiiframework.com/)
* [raven-php](https://github.com/getsentry/raven-php)
* [Sentry](https://github.com/getsentry/sentry)


### Requirements

* Yii 1.1 or above
* PHP curl extension


## Installation

* Extract the yii-raven-php under `protected/extensions`
* In your `protected/config/main.php`, add the following:

```php
<?php
...
$raven = array(
	'dsn' => 'put your DSN here',
	'logger' => 'backend',
	'server_name' => isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'unknown host',
//	'tags' => array(),
	'modules' => array(
		'php' => PHP_VERSION,
		'yii' => Yii::getVersion(),
	),
);
...
	'behaviors' => array(
		'RavenPHPApplicationBehavior' => array(
			'class' => 'ext.raven-php.ERavenPHPApplicationBehavior',
			'raven' => $raven,
//			'userClass' => 'common.components.raven-php.RavenPHPUser',
			'ignore404' => array(
				'favicon.ico', // url relative to app base url
//				array('/site/test'), // a route that throws 404 but shouldnt be logged
			),
		),
	),
		
... 
		
	'components'=>array(
		... 
		
		'log' => array(
			'class' => 'CLogRouter',
			'routes' => array(
				'error' => array(
					'class' => 'ext.raven-php.ERavenPHPLog',
					'raven' => array_merge($raven, array(
						'level' => 'error',
						'tags' => array(
							'type' => 'error',
						),
					)),
//					'userClass' => 'common.components.raven-php.RavenPHPUser',
					'levels' => 'error',
					'filter' => array(
						'class' => 'LogFilter',
						'ignoreCategories' => array(
							'exception.CHttpException.404',
							'exception.CHttpException.401',
						),
					),
				),

				'warning' => array(
					'class' => 'ext.raven-php.ERavenPHPLog',
					'raven' => array_merge($raven, array(
						'level' => 'error',
						'tags' => array(
							'type' => 'warning',
						),
					)),
//					'userClass' => 'common.components.raven-php.RavenPHPUser',
					'levels' => 'warning',
				),

				'notice' => array(
					'class' => 'ext.raven-php.ERavenPHPLog',
					'raven' => array_merge($raven, array(
						'level' => 'error',
						'tags' => array(
							'type' => 'notice',
						),
					)),
//					'userClass' => 'common.components.raven-php.RavenPHPUser',
					'levels' => 'notice',
				),

				/*'translation' => array(
					'class' => 'ext.raven-php.ERavenPHPLog',
					'raven' => array_merge($raven, array(
						'level' => 'warning',
						'tags' => array(
							'type' => 'translation',
						),
					)),
//					'userClass' => 'common.components.raven-php.RavenPHPUser',
					'levels' => 'translation',
				),*/
			),
		),
		
		...
	),
	
...
```


## Usage

PHP errors and uncaught exceptions will be logged automatically as configured above.

To manually log exception use `Yii::app()->logException($thrownException);` method.

See `ERavenPHPUser` class to how to change user attributes.


## License

The extension was released under the [New BSD License](http://www.opensource.org/licenses/bsd-license.php), so you'll find the latest version on [GitHub](https://github.com/Nodge/yii-raven-php).
