<!DOCTYPE html>
<html>
	<head>
		<title>Chessboard - Test</title>
		<meta charset="utf8">
		<style>
			* {
				margin: 0;
				padding: 0;
			}
			.rank {
				clear: both;
			}
			.square {
				width: 50px;
				height: 50px;
				float: left;
				font-size: 50px;
				border: 1px solid black;
			}
		</style>
	</head>
	<body>
		<?php
			include "chessboard.class.php";
			$board1 = new chessboard("", "8/1k6/8/8/8/8/1K6/8 w - - 0 1");
			echo $board1->getHTML();
			var_dump($board1->isValidMove("b2", "a3"));
		?>
	</body>
</html>