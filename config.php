<?php
return array(
	'server'   => 'irc.freenode.org',
	'serverPassword' => '',
	'port'	 => 6667,
	'name'	 => 'wildbot',
	'nick'	 => 'wildbot',
	'adminPassword' => '',
	'commandPrefix' => '!',
	'channels' => array(
		'#wildbot' => '',
	),
	'max_reconnects' => 1,
	'log_file'	   => 'log.txt',
	'commands'	   => array(
		'Command\Say'	 => '',
		'Command\Weather' => array(
			'yahooKey' => 'ChangeMe',
		),
		'Command\Joke'	=> '',
		'Command\Ip'	  => '',
		'Command\Yt'	  => '',
		'Command\Imdb'	=> '',
		'Command\Poke'	=> '',
		'Command\Join'	=> '',
		'Command\Part'	=> '',
		'Command\Timeout' => '',
		'Command\Quit'	=> '',
		'Command\Restart' => '',
		'Command\Serialise' => '',
		'Command\Remember'  => '',
	),
	'listeners' => array(
		'Listener\Joins' => '',
	),
);
