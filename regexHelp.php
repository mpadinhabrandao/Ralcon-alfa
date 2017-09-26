<?php
class RegexHelp{
	const VERSION = 'v[0-9]{1,}.[0-9]{1,}.[0-9]{1,}';
	const VERSIONLABEL = 'v[0-9]{1,}.[0-9]{1,}.[0-9]{1,}-[a-zA-Z0-9]+|v[0-9]{1,}.[0-9]{1,}.[0-9]{1,}';
	const VERSIONLABEL2 = 'v[\d]+.[\d]+.[\d]';
	const INSTALLABLE = '[a-zA-Z0-9]+';
	const PROJECT = '[a-zA-Z0-9]+';
	const ENVIRONMENT = '[a-zA-Z0-9]+';
	const TYPE = '[a-zA-Z0-9]+';
} 
