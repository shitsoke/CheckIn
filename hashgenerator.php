<?php
// Simple hash generator UI. Accepts a string via POST and outputs a bcrypt hash.
$generated = '';
$input = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	// Basic trimming; do not alter characters beyond trimming whitespace
	$input = isset($_POST['input']) ? trim($_POST['input']) : '';
	if ($input !== '') {
		$generated = password_hash($input, PASSWORD_BCRYPT);
	}
}
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<title>Hash Generator</title>
	<style>
		body { font-family: Arial, Helvetica, sans-serif; max-width:800px; margin:40px auto; padding:0 16px; }
		label { display:block; margin-bottom:8px; font-weight:600; }
		input[type=text], textarea { width:100%; padding:8px; box-sizing:border-box; font-size:14px; }
		.row { margin-bottom:12px; }
		button { padding:8px 12px; font-size:14px; }
		.output { background:#f6f6f6; padding:8px; border:1px solid #ddd; word-break:break-all; }
	</style>
</head>
<body>
	<h1>Hash Generator</h1>
	<form method="post" action="">
		<div class="row">
			<label for="input">String to hash</label>
			<input id="input" name="input" type="text" value="<?php echo htmlspecialchars($input, ENT_QUOTES); ?>" placeholder="Enter string (e.g. admin123)">
		</div>
		<div class="row">
			<button type="submit">Generate Hash</button>
			<button type="button" id="generate-random">Generate Random (16 bytes)</button>
		</div>
	</form>

	<?php if ($generated !== ''): ?>
		<h2>Generated Hash</h2>
		<div class="row">
			<textarea id="output" class="output" rows="3" readonly><?php echo htmlspecialchars($generated, ENT_QUOTES); ?></textarea>
		</div>
		<div class="row">
			<button id="copy">Copy to clipboard</button>
		</div>
	<?php endif; ?>

	<script>
	(function(){
		var btn = document.getElementById('copy');
		if (btn) btn.addEventListener('click', function(){
			var out = document.getElementById('output');
			if (!out) return;
			out.select();
			try {
				document.execCommand('copy');
				btn.textContent = 'Copied!';
				setTimeout(function(){ btn.textContent = 'Copy to clipboard'; }, 2000);
			} catch(e){
				alert('Copy not supported in this browser. Select and copy manually.');
			}
		});

		var genRand = document.getElementById('generate-random');
		if (genRand) genRand.addEventListener('click', function(){
			// Create a random 16-byte hex string locally and put it into the input
			var arr = new Uint8Array(16);
			if (window.crypto && crypto.getRandomValues) {
				crypto.getRandomValues(arr);
			} else {
				for (var i=0;i<16;i++) arr[i] = Math.floor(Math.random()*256);
			}
			var s = Array.prototype.map.call(arr, function(x){ return ('0'+x.toString(16)).slice(-2); }).join('');
			document.getElementById('input').value = s;
		});
	})();
	</script>
</body>
</html>
