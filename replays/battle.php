<?php

error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);

include_once 'theme/panels.lib.php';

if (!@$_REQUEST['name']) {
	include '404.php';
	die();
}
$manage = false;
$csrfOk = false;

if (isset($_REQUEST['manage'])) {
	require_once '../lib/ntbb-session.lib.php';
	if ($curuser['group'] != 2 && $curuser['group'] != 6) die("access denied");
	$csrfOk = !!$users->csrfCheck();
	$manage = true;
}

if (preg_match('/[^A-Za-z0-9-]/', $_REQUEST['name'])) die("access denied");

$replay = null;
$cached = false;

// $forcecache = isset($_REQUEST['forcecache8723']);
$forcecache = false;

if (file_exists('caches/' . $_REQUEST['name'] . '.inc.php')) {
	include 'caches/' . $_REQUEST['name'] . '.inc.php';
	$replay['formatid'] = '';
	$cached = true;
} else {
	require_once 'replays.lib.php';
	if (!$Replays->db && !$forcecache) {
		include '503.php';
		die();
	}
	$replay = $Replays->get($_REQUEST['name'], $forcecache);
}
if (!$replay) {
	include '404.php';
	die();
}

if (@$replay['private']) header('X-Robots-Tag: noindex');

if ($forcecache) {
	file_put_contents('caches/' . $_REQUEST['name'] . '.inc.php', '<?php $replay = ' . var_export($replay, true) . ';');
}

function userid($username) {
	if (!$username) $username = '';
	$username = strtr($username, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz");
	return preg_replace('/[^A-Za-z0-9]+/','',$username);
}

$replay['log'] = str_replace("\r","",$replay['log']);

// $matchSuccess = preg_match('/\\|player\\|p1\\|([^|]*)(\\|[^|]*)?\\n\\|player\\|p2\\|([^|]+)(\\|[^|]*)?\\n(\\|gametype\\|[^|]*\\n)?\\|tier\\|([^|]*)\\n/', $replay['log'], $matches);
$matchSuccess = preg_match('/\\n\\|tier\\|([^|]*)\\n/', $replay['log'], $matches);
$format = $replay['format'];
if ($matchSuccess) $format = $matches[1];

$panels->setPageTitle($format.' replay: '.$replay['p1'].' vs. '.$replay['p2']);
$panels->setPageDescription('Watch a replay of a Pokémon battle between ' . $replay['p1'] . ' and ' . $replay['p2'] . ' (' . $format . ')');
$panels->setTab('replay');
$panels->start();

?>
	<div class="pfx-panel"><div class="pfx-body" style="max-width:1180px">
		<div class="wrapper replay-wrapper">

			<div class="battle"><div class="playbutton"><button disabled>Loading...</button></div></div>
			<div class="battle-log"></div>
			<div class="replay-controls">
				<button data-action="start"><i class="fa fa-play"></i> Play</button>
			</div>
			<div class="replay-controls-2">
				<div class="chooser leftchooser speedchooser">
					<em>Speed:</em>
					<div><button value="hyperfast">Hyperfast</button> <button value="fast">Fast</button><button value="normal" class="sel">Normal</button><button value="slow">Slow</button><button value="reallyslow">Really Slow</button></div>
				</div>
				<div class="chooser colorchooser">
					<em>Color&nbsp;scheme:</em>
					<div><button class="sel" value="light">Light</button><button value="dark">Dark</button></div>
				</div>
				<div class="chooser soundchooser" style="display:none">
					<em>Music:</em>
					<div><button class="sel" value="on">On</button><button value="off">Off</button></div>
				</div>
			</div>
			<!--[if lte IE 8]>
				<div class="error"><p>&#3232;_&#3232; <strong>You're using an old version of Internet Explorer.</strong></p>
				<p>We use some transparent backgrounds, rounded corners, and other effects that your old version of IE doesn't support.</p>
				<p>Please install <em>one</em> of these: <a href="http://www.google.com/chrome">Chrome</a> | <a href="http://www.mozilla.org/en-US/firefox/">Firefox</a> | <a href="http://windows.microsoft.com/en-US/internet-explorer/products/ie/home">Internet Explorer 9</a></p></div>
			<![endif]-->

			<?php if (@$replay['private']) echo '<strong>THIS REPLAY IS PRIVATE</strong> - make sure you have the owner\'s permission to share<br />'; ?>

			<pre class="urlbox" style="word-wrap: break-word;"><?php echo htmlspecialchars('http://replay.pokemonshowdown.com/'.$_REQUEST['name']); ?></pre>

			<h1 style="font-weight:normal;text-align:left"><strong><?= htmlspecialchars($format) ?></strong>: <a href="//pokemonshowdown.com/users/<?= userid($replay['p1']) ?>" class="subtle"><?= htmlspecialchars($replay['p1']) ?></a> vs. <a href="//pokemonshowdown.com/users/<?= userid($replay['p2']) ?>" class="subtle"><?= htmlspecialchars($replay['p2']) ?></a></h1>
			<p style="padding:0 1em;margin-top:0">
				<small class="uploaddate" data-timestamp="<?= @$replay['uploadtime'] ?? @$replay['date'] ?>"><em>Uploaded:</em> <?php echo date("M j, Y", @$replay['uploadtime'] ?? @$replay['date']); ?><?= @$replay['rating'] ? ' | <em>Rating:</em> ' . $replay['rating'] : '' ?></small>
			</p>

			<div id="loopcount"></div>
<?php
if ($manage) {
	if ($csrfOk && isset($_POST['private'])) {
		$replay['private'] = intval($_POST['private']);
		$Replays->edit($replay);
		echo '<p>Edited.</p>';
	}
?>
			<form action="/<?= $replay['id'] ?>/manage" method="post" style="margin-top:1em" data-target="replace">
				<?php $users->csrfData(); ?>
				<input type="hidden" name="private" value="1" />
				<button type="submit" name="private" value="1">Private</button>
			</form>
			<form action="/<?= $replay['id'] ?>/manage" method="post" style="margin-top:1em" data-target="replace">
				<?php $users->csrfData(); ?>
				<input type="hidden" name="private" value="0" />
				<button type="submit" name="private" value="0">Public</button>
			</form>
<?php
}
?>
		</div>

		<input type="hidden" name="replayid" value="<?php echo htmlspecialchars($replay['id']); ?>" />
		<!--

You can get this log directly at https://replay.pokemonshowdown.com/<?php echo $replay['id']; ?>.log

Or with metadata at https://replay.pokemonshowdown.com/<?php echo $replay['id']; ?>.json

Most PS pages you'd want to scrape will have a .json version!

		-->
		<script type="text/plain" class="log"><?php if ($replay['id'] === 'smogtours-ou-509') readfile('js/smogtours-ou-509.log'); else if ($replay['id'] === 'ou-305002749') readfile('js/ou-305002749.log'); else echo str_replace('/','\\/',$replay['log']); ?></script>
<?php
if (substr($replay['formatid'], -12) === 'randombattle' || substr($replay['formatid'], -19) === 'randomdoublesbattle' || $replay['formatid'] === 'gen7challengecup' || $replay['formatid'] === 'gen7challengecup1v1' || $replay['formatid'] === 'gen7battlefactory' || $replay['formatid'] === 'gen7bssfactory' || $replay['formatid'] === 'gen7hackmonscup' || $manage) {
?>

		<script type="text/plain" class="inputlog"><?php echo str_replace('</','<\\/',$replay['inputlog']); ?></script>
<?php
}
?>

<?php
if ($panels->output === 'normal') {
?>
<div><script type="text/javascript"><!--
google_ad_client = "ca-pub-6535472412829264";
/* PS replay */
google_ad_slot = "6865298132";
google_ad_width = 728;
google_ad_height = 90;
//-->
</script>
<script type="text/javascript"
src="//pagead2.googlesyndication.com/pagead/show_ads.js">
</script></div>
<?php
}
?>

		<a href="/" class="pfx-backbutton" data-target="back"><i class="fa fa-chevron-left"></i> <?= $cached ? 'Other' : 'More' ?> replays</a>

	</div></div>
<?php

$panels->end();

?>
