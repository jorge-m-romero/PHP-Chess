<?php
session_start();

$validation = '';

if (isset($_GET['reset']) && $_GET['reset'] == 1)
{
    session_destroy();
    header('Location: index.php');
    $validation = 'Game have been reseted !';
}
?>

<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <link href="style/style.css" type="text/css" rel="stylesheet" media="all" />
        <title>Jeu d'Echec</title>
    </head>
    <body>
        <?php
        if (!empty($validation))
            echo '<p style="color: green;">' . $validation . '</p>';
        function autoloader($classname)
        {
            include 'classes/' . $classname . '.php';
        }

        spl_autoload_register('autoloader');

        if (!isset($_SESSION['board']))
        {
            $board = new Board();
            $board->Init();
        }
        else
        {
            $board = unserialize($_SESSION['board']);
        }

        if (isset($_GET['action']) && $_GET['action'] == 'promotion' &&
            isset($_GET['x']) && ctype_digit($_GET['x']) &&
            isset($_GET['y']) && ctype_digit($_GET['y']))
        {
            $origin = unserialize($_SESSION['origin']);
            $piece = $board->GetPiece($origin);
            $x = $_GET['x'];
            $y = $_GET['y'];

            echo '<div id="promotion-box"><h1>Please choose your promotion !</h1>';

            $pieceTypes = array('bishop', 'knight', 'rook', 'queen');
            foreach ($pieceTypes as $type)
            {
                echo '<a href="index.php?action=move_target&x=' . $x . '&y=' . $y . '&choice=' . $type . '"><img src="sprites/' . $piece->GetColor() . '_' . $type . '.png" /></a>';
            }
            
            echo '</div>';
        }
        else
        {
            if (!isset($_SESSION['logs']))
                $logs = new Log();
            else
            {
                $logs = unserialize($_SESSION['logs']);
            }

            if (empty($_GET))
            {
                $logs->Add($board->DisplayTurn(), 'info');
            }

            if (isset($_GET['action']))
            {
                switch ($_GET['action'])
                {
                    case 'move_origin':
                        if (isset($_GET['x']) && ctype_digit($_GET['x']) &&
                                isset($_GET['y']) && ctype_digit($_GET['y']))
                        {
                            $origin = new Position($_GET['x'], $_GET['y']);
                            $piece = $board->GetPiece($origin);
                            if ($piece !== null)
                            {
                                if ($piece->GetColor() == $board->GetTurn())
                                {
                                    // King is in check ?
                                    if ($piece->GetColor() == Color::White && $board->GetWhiteKing()->InCheck() && $piece !== $board->GetWhiteKing() ||
                                            $piece->GetColor() == Color::Black && $board->GetBlackKing()->InCheck() && $piece !== $board->GetBlackKing())
                                    {
                                        $logs->Add('Your king is under attack, you have to move it quickly !', 'warning');
                                        header('Location: index.php');
                                    }
                                    else
                                    {
                                        $piece->ComputePossibleCells($board);

                                        if (count($piece->GetPossibleCells()) == 0)
                                        {
                                            $logs->Add('No move available for this piece !', 'error');
                                            header('Location: index.php');
                                        }
                                        else
                                            $_SESSION['origin'] = serialize($origin);
                                    }
                                }
                                else
                                {
                                    $logs->Add('This is not your turn !', 'error');
                                }
                            }
                        }
                        break;
                    case 'move_target':
                        
                        if (isset($_SESSION['origin']))
                        {
                            if (isset($_GET['x']) && ctype_digit($_GET['x']) &&
                                    isset($_GET['y']) && ctype_digit($_GET['y']))
                            {
                                $origin = unserialize($_SESSION['origin']);
                                $piece = $board->GetPiece($origin);
                                $target = new Position($_GET['x'], $_GET['y']);

                                if ($board->IsPromotion($piece, $target) && empty($_GET['choice']))
                                {
                                    header('Location: index.php?action=promotion&x=' . $target->x . '&y=' . $target->y);
                                    exit;
                                }
                                else
                                {
                                    if (!empty($_GET['choice']))
                                        $_SESSION['promotion'] = $_GET['choice'];
                                    if ($board->Move($origin, $target))
                                    {
                                        $board->NextTurn();
                                    }
                                    else
                                    {
                                        $logs->Add('Invalid move !!', 'error');
                                    }
                                }
                            }
                            else
                            {
                                $logs->Add('Invalid move !', 'error');
                            }

                            unset($_SESSION['origin']);

                            header('Location: index.php');
                        }
                        break;
                    default:
                        break;
                }
            }

            if (isset($_GET['clear']) && $_GET['clear'] == 1)
            {
                $logs->Clear();
            }

            //$board->DisplayPossibleCells(Color::White);

            $_SESSION['board'] = serialize($board);
            $_SESSION['logs'] = serialize($logs);
            ?>

            <div id="board">
                <?php $board->DrawBoard(); ?>
            </div>

            <div id="info">
                <p style="text-align: center;">
                    <a href="index.php?reset=1" style="font-weight: bold; font-size: xx-large;">Reset the game !</a><br />
                    <a href="index.php?previous=1" style="float: left; font-style: italic;"><-- Previous</a>
                    <a href="index.php?clear=1" style="font-style: italic;">(Clear logs)</a>
                    <a href="index.php?next=1" style="float: right; font-style: italic;">Next --></a>
                </p>
                <div id="logs">
                    <?php
                    /*
                      echo '<pre>';
                      print_r($_SESSION);
                      echo '</pre>';
                     */
                    $logs->Display();
                    ?>
                </div>
            </div>
            <?php
            $board->DisplayHistory();
            $board->DisplayPieces();
        }
        ?>
    </body>
</html>