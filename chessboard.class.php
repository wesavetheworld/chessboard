<?php
	class chessboardException extends Exception {}
	
	/*
		Exception codes:
		1 - 100: General errors (empty arguments, ...)
			1: Unknown general error
			2: Empty/null argument
			3: Wrong argument type
			4: Invalid argument
	*/
	
	/**
	* A class to load FENs or PGNs and show them on a chessboard
	*/
	
	class chessboard
	{
		private $fen;
		private $pgn;
		private $board; /* 1st dimension: ranks (beginning with 1st rank), 2nd dimension: files (beginning with a-file) */
		private $turn;
		private $castlings; /* Associative array; Keys: K, Q, k, q */
		private $enPassant;
		private $halfMoves; /* number of half-moves since the last pawn move of capture */
		private $moveNumber;
		private $whitePieces = array("P", "N", "B", "R", "Q", "K");
		private $blackPieces = array("p", "n", "b", "r", "q", "k");
		
		function __construct($pgn = "", $fen = "rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1")
		{	
			/* load fen and pgn if not null */
			if (!empty($fen))
			{
				$this->loadFen($fen);
			}
			else
			{
				throw new chessboardException("__construct: fen may not be null", 2);
			}
			
			if (!empty($pgn))
			{
				$this->pgn = $pgn;
			}
			
		}
		
		/**
		* void loadFen (string fen)
		*
		* Loads a FEN
		*/
		
		function loadFen($fen)
		{
			if (!empty($fen))
			{
				$this->fen = $fen;
				
				/* split fen */
				$sections = explode(" ", $fen);
				
				/* validate fen */
				if (count($sections) == 6)
				{
					if (strpos($sections[0], "K") === FALSE || strpos($sections[0], "k") === FALSE)
					{
						throw new chessboardException("Function loadFen: there must be a black and a white king on the board.", 4);
					}
					/* split position into ranks */
					$ranks = explode("/", $sections[0]);
					
					/* set other values if valid */
					if (($sections[1] == "w") || ($sections[1] == "b"))
					{
						$this->turn = $sections[1];
					}
					else
					{
						throw new chessboardException("Function loadFen: " . $sections[1] . "is not a valid value for active color. Please use 'w' or 'b'.", 4);
					}
					
					/* set possible castlings */
					$this->castlings = array("K" => false, "Q" => false, "k" => false, "q" => false);
					
					if (strpos($sections[2], "K") !== false) /* '!== false' is necessary. See http://php.net/manual/en/function.strpos.php for further information */
					{
						$this->castlings["K"] = true;
					}
					
					if (strpos($sections[2], "Q") !== false)
					{
						$this->castlings["Q"] = true;
					}
					
					if (strpos($sections[2], "k") !== false)
					{
						$this->castlings["k"] = true;
					}
					
					if (strpos($sections[2], "q") !== false)
					{
						$this->castlings["q"] = true;
					}
					
					/* set en passant square */
					if ($sections[3] != "-")
					{
						if (preg_match("/[a-h][36]/", $sections[3])) /* check if a the value is a valid square on the 3rd or 6th rank */
						{
							$this->enPassant = $sections[3];
						}
						else
						{
							throw new chessboardException("Function loadFen: " . $sections[3] . " is not a valid en passant square", 4);
						}
					}
					else
					{
						/* no en passant square */
						$this->enPassant = "";
					}
					
					/* set number of half-moves since the last pawn move of capture */
					if (is_numeric($sections[4]))
					{
						$this->halfMoves = $sections[4];
					}
					else
					{
						throw new chessboardException("Function loadFen: half-moves must be a number", 3);
					}
					
					/* set move-number */
					if (is_numeric($sections[5]))
					{
						$this->moveNumber = $sections[5];
					}
					else
					{
						throw new chessboardException("Function loadFen: move-number must be a number", 3);
					}
					
					$i_rank = 0;
					foreach ($ranks as $rank)
					{
						$i_file = 0;
						$this->board[] = array();
						while (!empty($rank))
						{
							if (is_numeric(substr($rank, 0, 1)))
							{
								$this->board[$i_rank][] = "";
								$new = ((int) substr($rank, 0, 1)) - 1;
								if ($new == 0)
								{
									$new = "";
								}
								$rank = $new . substr($rank, 1);
							}
							else
							{
								$this->board[$i_rank][] = substr($rank, 0, 1);
								$rank = substr($rank, 1);
							}
							
							$i_file++;
						}
						
						$i_rank++;
					}
					$this->board = array_reverse($this->board);
				}
				else
				{
					throw new chessboardException("Function loadFen: fen must have 6 sections", 4);
				}
				
			}
			else
			{
				throw new chessboardException("Function loadFen: Argument fen may not be empty", 2);
			}
		}
		
		/*
		* checks if a move is possible, but ignores if it would leave the king in check and whose turn it is
		*/
		
		function isPossibleMove($start, $target)
		{
			if ((strlen((string)$start) == 2) && (strlen((string)$target) == 2))
		    {
		        if (preg_match("/[a-h][1-8]/", (string)$start))
		        {
		            switch (substr($start, 0, 1))
		            {
		                case "a":
		                    $start = "1" . substr($start, 1, 1);
		                    break;
		                case "b":
		                    $start = "2" . substr($start, 1, 1);
		                    break;
		                case "c":
		                    $start = "3" . substr($start, 1, 1);
		                    break;
		                case "d":
		                    $start = "4" . substr($start, 1, 1);
		                    break;
		                case "e":
		                    $start = "5" . substr($start, 1, 1);
		                    break;
		                case "f":
		                    $start = "6" . substr($start, 1, 1);
		                    break;
		                case "g":
		                    $start = "7" . substr($start, 1, 1);
		                    break;
		                case "h":
		                    $start = "8" . substr($start, 1, 1);
		                    break;
		                default:
		                    throw new chessboardException("Function isPossibleMove: argument start must start with a number or a letter from a to h", 4);
		            }
		        }
		        else
		        {
		            if (!preg_match("/[1-8][1-8]/", (string)$start))
		            {
		                throw new chessboardException("Function isPossibleMove: argument start must start with a number or a letter from a to h", 4);
		            }
		        }
		        
		        if (preg_match("/[a-h][1-8]/", (string)$target))
		        {
		            switch (substr($target, 0, 1))
		            {
		                case "a":
		                    $target = "1" . substr($target, 1, 1);
		                    break;
		                case "b":
		                    $target = "2" . substr($target, 1, 1);
		                    break;
		                case "c":
		                    $target = "3" . substr($target, 1, 1);
		                    break;
		                case "d":
		                    $target = "4" . substr($target, 1, 1);
		                    break;
		                case "e":
		                    $target = "5" . substr($target, 1, 1);
		                    break;
		                case "f":
		                    $target = "6" . substr($target, 1, 1);
		                    break;
		                case "g":
		                    $target = "7" . substr($target, 1, 1);
		                    break;
		                case "h":
		                    $target = "8" . substr($target, 1, 1);
		                    break;
		                default:
		                    throw new chessboardException("Function isPossibleMove: argument target must start with a number or a letter from a to h", 4);
		            }
		        }
		        else
		        {
		            if (!preg_match("/[1-8][1-8]/", (string)$target))
		            {
		                throw new chessboardException("Function isPossibleMove: argument target must start with a number or a letter from a to h", 4);
		            }
		        }
		    }
		    else
		    {
		        throw new chessboardException("Function isPossibleMove: start and target must have a length of two", 4);
		    }
		    $start = array((int)substr($start, 0, 1) - 1, ((int)substr($start, 1, 1)) - 1);
		    $target = array((int)substr($target, 0, 1) - 1, ((int)substr($target, 1, 1)) - 1);
		    
		    if ($start == $target)  /* Start and target square are the same */
		    {
		        return FALSE;
		    }
		    
		    switch ($this->board[$start[1]][$start[0]])
		    {
		        case "":
		            return FALSE;
		            break;
		            
		        case "N": /* white Knight */
		            
					if (((abs($start[0] - $target[0]) == 2) && (abs($start[1] - $target[1]) == 1)) || ((abs($start[0] - $target[0]) == 1) && (abs($start[1] - $target[1]) == 2)))
					{
						if (!in_array($this->board[$target[1]][$target[0]], $this->whitePieces)) /* check if a piece of the same color is on the square */
						{
							return TRUE;  
						}
						else
						{
							return FALSE;
						}
					}
					else
					{
						return FALSE;
					}
				
		            break;
		            
		        case "n": /* black Knight */
		           
					if (((abs($start[0] - $target[0]) == 2) && (abs($start[1] - $target[1]) == 1)) || ((abs($start[0] - $target[0]) == 1) && (abs($start[1] - $target[1]) == 2)))
					{
						if (!in_array($this->board[$target[1]][$target[0]], $this->blackPieces)) /* check if a piece of the same color is on the square */
						{
							return TRUE;  
						}
						else
						{
							return FALSE;
						}
					}
					else
					{
						return FALSE;
					}
		            
		            
		            break;
		          
		        case "K": /* white King */
		            
					if ((abs($start[0] - $target[0]) == 1 && abs($start[1] - $target[1]) == 1) || (abs($start[0] - $target[0]) == 0 && abs($start[1] - $target[1]) == 1) || (abs($start[0] - $target[0]) == 1 && abs($start[1] - $target[1]) == 0))
					{
						if (!in_array($this->board[$target[1]][$target[0]], $this->whitePieces)) /* check if a piece of the same color is on the square */
						{
							return TRUE;  
						}
						else
						{
							return FALSE;
						}
					}
					else
					{
						return FALSE;
					}
		            
		            break;
		          
		        case "k": /* black King */
		            
					if ((abs($start[0] - $target[0]) == 1 && abs($start[1] - $target[1]) == 1) || (abs($start[0] - $target[0]) == 0 && abs($start[1] - $target[1]) == 1) || (abs($start[0] - $target[0]) == 1 && abs($start[1] - $target[1]) == 0))
					{
						if (!in_array($this->board[$target[1]][$target[0]], $this->blackPieces)) /* check if a piece of the same color is on the square */
						{
							return TRUE;  
						}
						else
						{
							return FALSE;
						}
					}
					else
					{
						return FALSE;
					}
		            
		            break;
				
				case "B": /* white bishop */
					
					if (abs($start[0] - $target[0]) == abs($start[1] - $target[1]))
					{
						$x = $start[0];
						$y = $start[1];
						$change_x = ($target[0] - $start[0]) / abs($start[0] - $target[0]);
						$change_y = ($target[1] - $start[1]) / abs($start[1] - $target[1]);
						$x += $change_x;
						$y += $change_y;
						while (!($x == $target[0] && $y == $target[1]))
						{
							
							if (!empty($this->board[$y][$x]))
							{
								return FALSE;
							}
							$x += $change_x;
							$y += $change_y;
						}
						if (!in_array($this->board[$target[1]][$target[0]], $this->whitePieces)) /* check if a piece of the same color is on the square */
						{
							return TRUE;  
						}
						else
						{
							return FALSE;
						}
					}
					else
					{
						return FALSE;
					}
		            
		            break;
					
				case "b": /* black bishop */
					
					if (abs($start[0] - $target[0]) == abs($start[1] - $target[1]))
					{
						$x = $start[0];
						$y = $start[1];
						$change_x = ($target[0] - $start[0]) / abs($start[0] - $target[0]);
						$change_y = ($target[1] - $start[1]) / abs($start[1] - $target[1]);
						$x += $change_x;
						$y += $change_y;
						while (!($x == $target[0] && $y == $target[1]))
						{
							
							if (!empty($this->board[$y][$x]))
							{
								return FALSE;
							}
							$x += $change_x;
							$y += $change_y;
						}
						if (!in_array($this->board[$target[1]][$target[0]], $this->blackPieces)) /* check if a piece of the same color is on the square */
						{
							return TRUE;  
						}
						else
						{
							return FALSE;
						}
					}
					else
					{
						return FALSE;
					}
		            
		            break;
				
				case "R": /* white rook */
					
					if (($start[0] - $target[0] == 0) || ($start[1] - $target[1] == 0))
					{
						$change_x = (($target[0] - $start[0] == 0) ? 0 : ($target[0] - $start[0]) / abs($start[0] - $target[0]));
						$change_y = (($target[1] - $start[1] == 0) ? 0 : ($target[1] - $start[1]) / abs($start[1] - $target[1]));
						$x = $start[0];
						$y = $start[1];
						$x += $change_x;
						$y += $change_y;
						while (!($x == $target[0] && $y == $target[1]))
						{
							if (!empty($this->board[$y][$x]))
							{
								return FALSE;
							}
							$x += $change_x;
							$y += $change_y;
						}
						if (!in_array($this->board[$target[1]][$target[0]], $this->whitePieces)) /* check if a piece of the same color is on the square */
						{
							return TRUE;  
						}
						else
						{
							return FALSE;
						}
					}
					else
					{
						return FALSE;
					}
		            
		            break;
				
				case "r": /* black rook */
					
					if (($start[0] - $target[0] == 0) || ($start[1] - $target[1] == 0))
					{
						$change_x = (($target[0] - $start[0] == 0) ? 0 : ($target[0] - $start[0]) / abs($start[0] - $target[0]));
						$change_y = (($target[1] - $start[1] == 0) ? 0 : ($target[1] - $start[1]) / abs($start[1] - $target[1]));
						$x = $start[0];
						$y = $start[1];
						$x += $change_x;
						$y += $change_y;
						while (!($x == $target[0] && $y == $target[1]))
						{
							if (!empty($this->board[$y][$x]))
							{
								return FALSE;
							}
							$x += $change_x;
							$y += $change_y;
						}
						if (!in_array($this->board[$target[1]][$target[0]], $this->blackPieces)) /* check if a piece of the same color is on the square */
						{
							return TRUE;  
						}
						else
						{
							return FALSE;
						}
					}
					else
					{
						return FALSE;
					}
		            
		            break;
					
				case "Q": /* white queen */
					if (in_array($this->board[$target[1]][$target[0]], $this->whitePieces)) /* check if a piece of the same color is on the square */
					{
						return FALSE;  
					}

					if (($start[0] - $target[0] == 0) || ($start[1] - $target[1] == 0))
					{
						$change_x = (($target[0] - $start[0] == 0) ? 0 : ($target[0] - $start[0]) / abs($start[0] - $target[0]));
						$change_y = (($target[1] - $start[1] == 0) ? 0 : ($target[1] - $start[1]) / abs($start[1] - $target[1]));
						$x = $start[0];
						$y = $start[1];
						$x += $change_x;
						$y += $change_y;
						while (!($x == $target[0] && $y == $target[1]))
						{
							if (!empty($this->board[$y][$x]))
							{
								return FALSE;
							}
							$x += $change_x;
							$y += $change_y;
						}
						return TRUE;	
					}
					if (abs($start[0] - $target[0]) == abs($start[1] - $target[1]))
					{
						$x = $start[0];
						$y = $start[1];
						$change_x = ($target[0] - $start[0]) / abs($start[0] - $target[0]);
						$change_y = ($target[1] - $start[1]) / abs($start[1] - $target[1]);
						$x += $change_x;
						$y += $change_y;
						while (!($x == $target[0] && $y == $target[1]))
						{
							
							if (!empty($this->board[$y][$x]))
							{
								return FALSE;
							}
							$x += $change_x;
							$y += $change_y;
						}
						return TRUE;
					}
					return FALSE;
		            break;
					
				case "q": /* black queen */
					if (in_array($this->board[$target[1]][$target[0]], $this->blackPieces)) /* check if a piece of the same color is on the square */
					{
						return FALSE;  
					}

					if (($start[0] - $target[0] == 0) || ($start[1] - $target[1] == 0))
					{
						$change_x = (($target[0] - $start[0] == 0) ? 0 : ($target[0] - $start[0]) / abs($start[0] - $target[0]));
						$change_y = (($target[1] - $start[1] == 0) ? 0 : ($target[1] - $start[1]) / abs($start[1] - $target[1]));
						$x = $start[0];
						$y = $start[1];
						$x += $change_x;
						$y += $change_y;
						while (!($x == $target[0] && $y == $target[1]))
						{
							if (!empty($this->board[$y][$x]))
							{
								return FALSE;
							}
							$x += $change_x;
							$y += $change_y;
						}
						return TRUE;	
					}
					if (abs($start[0] - $target[0]) == abs($start[1] - $target[1]))
					{
						$x = $start[0];
						$y = $start[1];
						$change_x = ($target[0] - $start[0]) / abs($start[0] - $target[0]);
						$change_y = ($target[1] - $start[1]) / abs($start[1] - $target[1]);
						$x += $change_x;
						$y += $change_y;
						while (!($x == $target[0] && $y == $target[1]))
						{
							
							if (!empty($this->board[$y][$x]))
							{
								return FALSE;
							}
							$x += $change_x;
							$y += $change_y;
						}
						return TRUE;
					}
					return FALSE;
		            break;
				
				case "P": /* white pawn */
					if (in_array($this->board[$target[1]][$target[0]], $this->whitePieces)) /* check if a piece of the same color is on the square */
					{
						return FALSE;  
					}

					if ($target[1] - $start[1] == 1)
					{
						return TRUE;	
					}
					if (($target[1] - $start[1] == 1 ) && (abs($target[0] - $start[0]) == 1) && (!empty($this->board[$target[1]][$target[0]])))
					{
						return TRUE;
					}
					return FALSE;
		            break;
					
				case "p": /* black pawn */
					if (in_array($this->board[$target[1]][$target[0]], $this->blackPieces)) /* check if a piece of the same color is on the square */
					{
						return FALSE;  
					}

					if ($target[1] - $start[1] == -1)
					{
						return TRUE;	
					}
					if (($target[1] - $start[1] == -1 ) && (abs($target[0] - $start[0]) == 1) && (!empty($this->board[$target[1]][$target[0]])))
					{
						return TRUE;
					}
					return FALSE;
		            return TRUE;
					
		    }
		}
		
		function isValidMove($start, $target)
		{
			if ((strlen((string)$start) == 2) && (strlen((string)$target) == 2))
		    {
		        if (preg_match("/[a-h][1-8]/", (string)$start))
		        {
		            switch (substr($start, 0, 1))
		            {
		                case "a":
		                    $start = "1" . substr($start, 1, 1);
		                    break;
		                case "b":
		                    $start = "2" . substr($start, 1, 1);
		                    break;
		                case "c":
		                    $start = "3" . substr($start, 1, 1);
		                    break;
		                case "d":
		                    $start = "4" . substr($start, 1, 1);
		                    break;
		                case "e":
		                    $start = "5" . substr($start, 1, 1);
		                    break;
		                case "f":
		                    $start = "6" . substr($start, 1, 1);
		                    break;
		                case "g":
		                    $start = "7" . substr($start, 1, 1);
		                    break;
		                case "h":
		                    $start = "8" . substr($start, 1, 1);
		                    break;
		                default:
		                    throw new chessboardException("Function isValidMove: argument start must start with a number or a letter from a to h", 4);
		            }
		        }
		        else
		        {
		            if (!preg_match("/[1-8][1-8]/", (string)$start))
		            {
		                throw new chessboardException("Function isValidMove: argument start must start with a number or a letter from a to h", 4);
		            }
		        }
		        
		        if (preg_match("/[a-h][1-8]/", (string)$target))
		        {
		            switch (substr($target, 0, 1))
		            {
		                case "a":
		                    $target = "1" . substr($target, 1, 1);
		                    break;
		                case "b":
		                    $target = "2" . substr($target, 1, 1);
		                    break;
		                case "c":
		                    $target = "3" . substr($target, 1, 1);
		                    break;
		                case "d":
		                    $target = "4" . substr($target, 1, 1);
		                    break;
		                case "e":
		                    $target = "5" . substr($target, 1, 1);
		                    break;
		                case "f":
		                    $target = "6" . substr($target, 1, 1);
		                    break;
		                case "g":
		                    $target = "7" . substr($target, 1, 1);
		                    break;
		                case "h":
		                    $target = "8" . substr($target, 1, 1);
		                    break;
		                default:
		                    throw new chessboardException("Function isValidMove: argument target must start with a number or a letter from a to h", 4);
		            }
		        }
		        else
		        {
		            if (!preg_match("/[1-8][1-8]/", (string)$target))
		            {
		                throw new chessboardException("Function isValidMove: argument target must start with a number or a letter from a to h", 4);
		            }
		        }
		    }
		    else
		    {
		        throw new chessboardException("Function isValidMove: start and target must have a length of two", 4);
		    }
			
			if (!$this->isPossibleMove($start, $target))
			{
				return FALSE;
			}
			
		    $start = array((int)substr($start, 0, 1) - 1, ((int)substr($start, 1, 1)) - 1);
		    $target = array((int)substr($target, 0, 1) - 1, ((int)substr($target, 1, 1)) - 1);
			
			if (in_array($this->board[$start[1]][$start[0]], $this->whitePieces) && $this->turn == "b")
			{
				return FALSE;
			}
			if (in_array($this->board[$start[1]][$start[0]], $this->blackPieces) && $this->turn == "w")
			{
				return FALSE;
			}
			
			$board_temp = $this->board;
			$board_temp[$start[1]][$start[0]] = "";
			$board_temp[$target[1]][$target[0]] = $this->board[$start[1]][$start[0]];
			
			foreach ($board_temp as $rankNumber => $rank)
			{
				foreach ($rank as $fileNumber => $square)
				{
					if ($this->turn == "w")
					{
						if ($square == "K")
						{
							$kingPosition = ($fileNumber + 1) . ($rankNumber + 1);
						}
					}
					if ($this->turn == "b")
					{
						if ($square == "k")
						{
							$kingPosition = ($fileNumber + 1) . ($rankNumber + 1);
						}
					}
				}
				
			}
			
			foreach ($board_temp as $rankNumber => $rank)
			{
				foreach ($rank as $fileNumber => $square)
				{
					if ($this->turn == "w")
					{
						if (in_array($square, $this->blackPieces))
						{
							if ($this->isPossibleMove(($fileNumber + 1) . ($rankNumber + 1), $kingPosition))
							{
								return FALSE;
							}
						}
					}
					if ($this->turn == "b")
					{
						if (in_array($square, $this->whitePieces))
						{
							if ($this->isPossibleMove(($fileNumber + 1) . ($rankNumber + 1), $kingPosition))
							{
								return FALSE;
							}
						}
					}
				}
			}
			
			return TRUE;
		}
		
		function doMove($start, $target)
		{
			if ((strlen((string)$start) == 2) && (strlen((string)$target) == 2))
		    {
		        if (preg_match("/[a-h][1-8]/", (string)$start))
		        {
		            switch (substr($start, 0, 1))
		            {
		                case "a":
		                    $start = "1" . substr($start, 1, 1);
		                    break;
		                case "b":
		                    $start = "2" . substr($start, 1, 1);
		                    break;
		                case "c":
		                    $start = "3" . substr($start, 1, 1);
		                    break;
		                case "d":
		                    $start = "4" . substr($start, 1, 1);
		                    break;
		                case "e":
		                    $start = "5" . substr($start, 1, 1);
		                    break;
		                case "f":
		                    $start = "6" . substr($start, 1, 1);
		                    break;
		                case "g":
		                    $start = "7" . substr($start, 1, 1);
		                    break;
		                case "h":
		                    $start = "8" . substr($start, 1, 1);
		                    break;
		                default:
		                    throw new chessboardException("Function doMove: argument start must start with a number or a letter from a to h", 4);
		            }
		        }
		        else
		        {
		            if (!preg_match("/[1-8][1-8]/", (string)$start))
		            {
		                throw new chessboardException("Function doMove: argument start must start with a number or a letter from a to h", 4);
		            }
		        }
		        
		        if (preg_match("/[a-h][1-8]/", (string)$target))
		        {
		            switch (substr($target, 0, 1))
		            {
		                case "a":
		                    $target = "1" . substr($target, 1, 1);
		                    break;
		                case "b":
		                    $target = "2" . substr($target, 1, 1);
		                    break;
		                case "c":
		                    $target = "3" . substr($target, 1, 1);
		                    break;
		                case "d":
		                    $target = "4" . substr($target, 1, 1);
		                    break;
		                case "e":
		                    $target = "5" . substr($target, 1, 1);
		                    break;
		                case "f":
		                    $target = "6" . substr($target, 1, 1);
		                    break;
		                case "g":
		                    $target = "7" . substr($target, 1, 1);
		                    break;
		                case "h":
		                    $target = "8" . substr($target, 1, 1);
		                    break;
		                default:
		                    throw new chessboardException("Function doMove: argument target must start with a number or a letter from a to h", 4);
		            }
		        }
		        else
		        {
		            if (!preg_match("/[1-8][1-8]/", (string)$target))
		            {
		                throw new chessboardException("Function doMove: argument target must start with a number or a letter from a to h", 4);
		            }
		        }
		    }
		    else
		    {
		        throw new chessboardException("Function isValidMove: start and target must have a length of two", 4);
		    }
		    $start = array((int)substr($start, 0, 1) - 1, ((int)substr($start, 1, 1)) - 1);
		    $target = array((int)substr($target, 0, 1) - 1, ((int)substr($target, 1, 1)) - 1);
		}
		
		/**
		* string getHTML ()
		*
		* Returns HTML-Code for the board
		*
		* only for testing
		*/
		
		function getHTML()
		{
			$return = "";
			foreach (array_reverse($this->board) as $rank)
			{
			    $return .= "<div class='rank'>";
				foreach ($rank as $square)
				{
					$return .= "<div class='square'>";
					$return .= $square;
					$return .= "</div>";
				}
				$return .= "</div>";
			}
			return $return;
		}
		
	}
?>
