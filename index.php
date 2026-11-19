<?php
    /**********************************************************************
     * GROUP TASKS — finish the casino!
     * The game runs, but the house keeps every bet and the table is almost
     * silent. Search for "GROUP TASK" to find where each piece goes:
     *   1. Play "deal.wav" whenever cards are dealt (2 spots)
     *   2. Play "win.wav" / "lose.wav" when a round is decided (2 spots)
     *   3. Pay the winners! (the dealer's settlement loop)
     *   4. Blackjack on the deal pays 3 to 2 immediately
     *   5. Broke players get re-staked, and reshuffles return bets (2 spots)
     **********************************************************************/

    session_start();

    //Sets up the multi-dimensional array of all the cards in a deck (paste from deck.txt)
    $newDeck = [['card' => "2C.jpg", 'value' => 2], ['card' => "2D.jpg", 'value' => 2], ['card' => "2H.jpg", 'value' => 2], ['card' => "2S.jpg", 'value' => 2],
                ['card' => "3C.jpg", 'value' => 3], ['card' => "3D.jpg", 'value' => 3], ['card' => "3H.jpg", 'value' => 3], ['card' => "3S.jpg", 'value' => 3],
                ['card' => "4C.jpg", 'value' => 4], ['card' => "4D.jpg", 'value' => 4], ['card' => "4H.jpg", 'value' => 4], ['card' => "4S.jpg", 'value' => 4],
                ['card' => "5C.jpg", 'value' => 5], ['card' => "5D.jpg", 'value' => 5], ['card' => "5H.jpg", 'value' => 5], ['card' => "5S.jpg", 'value' => 5],
                ['card' => "6C.jpg", 'value' => 6], ['card' => "6D.jpg", 'value' => 6], ['card' => "6H.jpg", 'value' => 6], ['card' => "6S.jpg", 'value' => 6],
                ['card' => "7C.jpg", 'value' => 7], ['card' => "7D.jpg", 'value' => 7], ['card' => "7H.jpg", 'value' => 7], ['card' => "7S.jpg", 'value' => 7],
                ['card' => "8C.jpg", 'value' => 8], ['card' => "8D.jpg", 'value' => 8], ['card' => "8H.jpg", 'value' => 8], ['card' => "8S.jpg", 'value' => 8],
                ['card' => "9C.jpg", 'value' => 9], ['card' => "9D.jpg", 'value' => 9], ['card' => "9H.jpg", 'value' => 9], ['card' => "9S.jpg", 'value' => 9],
                ['card' => "10C.jpg", 'value' => 10], ['card' => "10D.jpg", 'value' => 10], ['card' => "10H.jpg", 'value' => 10], ['card' => "10S.jpg", 'value' => 10],
                ['card' => "JC.jpg", 'value' => 10], ['card' => "JD.jpg", 'value' => 10], ['card' => "JH.jpg", 'value' => 10], ['card' => "JS.jpg", 'value' => 10],
                ['card' => "QC.jpg", 'value' => 10], ['card' => "QD.jpg", 'value' => 10], ['card' => "QH.jpg", 'value' => 10], ['card' => "QS.jpg", 'value' => 10],
                ['card' => "KC.jpg", 'value' => 10], ['card' => "KD.jpg", 'value' => 10], ['card' => "KH.jpg", 'value' => 10], ['card' => "KS.jpg", 'value' => 10],
                ['card' => "AC.jpg", 'value' => 11], ['card' => "AD.jpg", 'value' => 11], ['card' => "AH.jpg", 'value' => 11], ['card' => "AS.jpg", 'value' => 11]];

    //Check to see if session variables were setup, if not initate them
    if (!isset($_SESSION['deck'])) {
        $_SESSION['deck'] = $newDeck;
    }
    if (!isset($_SESSION['dhand'])) {
        $_SESSION['dhand'] = [];
        $_SESSION['dscore'] = 0;
    }
    //Tracks whether the dealer's first card should be shown (after every player has finished)
    if (!isset($_SESSION['reveal'])) {
        $_SESSION['reveal'] = false;
    }

    //Holds a message for the player; printed as a script at the bottom of the page
    //so the alert pops up after the page has rendered
    $message = "";

    //Works just like $message: the handlers below pick a sound, and a script
    //at the bottom of the page plays it after the page has rendered
    $sound = "";

    //Each player is an associative array: a name, a hand of cards, a score,
    //their money, and their current bet. $_SESSION['active'] is the index of
    //whoever's turn it is right now.
    if (!isset($_SESSION['players'])) {
        $_SESSION['players'] = [];
    }
    if (!isset($_SESSION['active'])) {
        $_SESSION['active'] = 0;
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['addplayer'])) {
        $name = trim($_POST['pname']);
        //Every new player starts with $100, an empty hand, and no bet.
        //Six players plus the dealer is the most one deck can comfortably handle.
        if ($name != "" && count($_SESSION['players']) < 6) {
            $_SESSION['players'][] = ['name' => $name, 'hand' => [], 'score' => 0, 'money' => 100, 'bet' => 0];
        }
    }

    //If the form was posted, and the option selected was shuffle, reset the deck and then shuffle it
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['shuffle'])) {
        //if the count is less than 52, reset the deck, as well as all hands and scores
        if (count($_SESSION['deck']) < 52) {
            $_SESSION['deck'] = $newDeck;
            $_SESSION['dhand'] = [];
            $_SESSION['dscore'] = 0;
            $_SESSION['reveal'] = false;

            //reset every player
            foreach ($_SESSION['players'] as $i => $p) {
                $_SESSION['players'][$i]['hand'] = [];
                $_SESSION['players'][$i]['score'] = 0;
                //********** GROUP TASK 5 (part 2) **********
                //Reshuffling abandons the round, but right now any bet still on
                //the table just vanishes. Give it back to its player before the
                //bet gets zeroed out below!
                //*******************************************
                $_SESSION['players'][$i]['bet'] = 0;
            }
            $_SESSION['active'] = 0;
        }
        //randomize the order of the array items
        shuffle($_SESSION['deck']);

        //play the shuffle sound
        $sound = "shuffle.wav";
    }
    
    //Adds up the score of a hand. Aces start at 11, but drop to 1 if the hand would bust
    function scoreHand($hand) {
        $score = 0;
        $aces = 0;

        //Add up every card, and count how many aces we have
        foreach($hand as $card) {
            $score += $card['value'];
            if ($card['value'] == 11) {
                $aces++;
            }
        }

        //While the hand busts and we still have an ace counted as 11, count it as 1 instead
        while ($score > 21 && $aces > 0) {
            $score -= 10;
            $aces--;
        }

        return $score;
    }

    //The dealer's turn: flip the hidden card, hit to 17, then settle every bet
    function dealerTurn() {
        //$message and $sound live outside this function, so we have to ask for them
        global $message, $sound;

        $_SESSION['reveal'] = true;

        //Casino rules: the dealer must keep hitting until they reach 17 or higher
        while ($_SESSION['dscore'] < 17) {
            $_SESSION['dhand'][] = array_pop($_SESSION['deck']);
            $_SESSION['dscore'] = scoreHand($_SESSION['dhand']);
        }

        if ($_SESSION['dscore'] > 21) {
            $message .= " Dealer busted!";
        }

        //Settle every player who still has a bet on the table
        //********** GROUP TASK 3: Payouts **********
        //Right now the casino keeps every bet, no matter who wins!
        //Remember: the bet already left the player's money when it was placed, so
        //  - a WINNER gets their bet back, doubled
        //  - a PUSH gets just their bet back
        //  - a LOSER gets nothing
        //*******************************************
        //********** GROUP TASK 2 (part 1): Outcome sounds **********
        //Play "win.wav" if anyone at the table won, otherwise "lose.wav".
        //Careful: don't let a later loser overwrite somebody's win sound!
        //***********************************************************
        $anyWinner = false;
        foreach ($_SESSION['players'] as $i => $p) {
            if ($p['bet'] > 0) {
                if ($_SESSION['dscore'] > 21 || $p['score'] > $_SESSION['dscore']) {
                    $_SESSION['players'][$i]['money'] += 2 * $p['bet'];
                    $message .= " {$p['name']} wins!";
                    $anyWinner = true;
                } else if ($p['score'] < $_SESSION['dscore']) {
                    $message .= " {$p['name']} loses.";
                } else {
                    $_SESSION['players'][$i]['money'] += $p['bet'];
                    $message .= " {$p['name']} pushes.";
                }
                $_SESSION['players'][$i]['bet'] = 0;
            }
        }
        $sound = $anyWinner ? "win.wav" : "lose.wav";
    }

    //Moves play to the next player who still has a live bet.
    //Once every player has finished, the dealer takes their turn.
    function nextTurn() {
        $_SESSION['active']++;
        //skip players whose round is already settled (blackjack at the deal, or bust)
        while ($_SESSION['active'] < count($_SESSION['players'])
                && $_SESSION['players'][$_SESSION['active']]['bet'] == 0) {
            $_SESSION['active']++;
        }
        //past the last player means everyone has played
        if ($_SESSION['active'] >= count($_SESSION['players'])) {
            dealerTurn();
        }
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['start'])) {
        //there has to be somebody at the table
        if (count($_SESSION['players']) == 0) {
            $message = "Add at least one player first!";
        //Check if the deck needs to be reset first
        } else if (count($_SESSION['deck']) < 52) {
            $message = "Please shuffle and reset the deck first";
        } else {
            //check every bet before any cards are dealt.
            //The inputs are named bet[0], bet[1], ... so $_POST['bet'] is an array!
            $ok = true;
            foreach ($_SESSION['players'] as $i => $p) {
                $bet = (int)$_POST['bet'][$i];
                if ($bet < 1 || $bet > $p['money']) {
                    $message = "{$p['name']}'s bet must be between $1 and $" . $p['money'];
                    $ok = false;
                }
            }

            if ($ok) {
                //********** GROUP TASK 1 (part 1) **********
                //Play "deal.wav" when the cards go out.
                //(The shuffle handler shows how a sound gets played.)
                //*******************************************

                $sound = "deal.wav";
                //take each bet and deal each player two cards.
                //Like a real casino, the chips leave your stack as soon as you bet;
                //winning pays the stake back plus the winnings
                foreach ($_SESSION['players'] as $i => $p) {
                    $bet = (int)$_POST['bet'][$i];
                    $_SESSION['players'][$i]['bet'] = $bet;
                    $_SESSION['players'][$i]['money'] -= $bet;
                    $_SESSION['players'][$i]['hand'] = [array_pop($_SESSION['deck']), array_pop($_SESSION['deck'])];
                    $_SESSION['players'][$i]['score'] = scoreHand($_SESSION['players'][$i]['hand']);

                    //********** GROUP TASK 4: BLACKJACK! **********
                    //If this player's first two cards score 21, announce it in the
                    //message, play the win sound, and pay them 3 to 2 right away:
                    //their bet back PLUS 1.5x the bet (use floor() to avoid 50 cents).
                    //Then set their bet to 0 — a settled bet is how the turn order
                    //knows to skip right past them.
                    //**********************************************
                }

                //the dealer gets two cards as well
                $_SESSION['dhand'][] = array_pop($_SESSION['deck']);
                $_SESSION['dhand'][] = array_pop($_SESSION['deck']);
                $_SESSION['dscore'] = scoreHand($_SESSION['dhand']);

                //play starts with the first player who still has a live bet.
                //Starting at -1 lets nextTurn() handle skipping settled players.
                $_SESSION['active'] = -1;
                nextTurn();
            }
        }
    }

    //Hit — deal the active player a card
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['hit'])) {
        $i = $_SESSION['active'];
        //hitting only makes sense while someone's turn is in progress
        if ($i < count($_SESSION['players']) && $_SESSION['players'][$i]['bet'] > 0) {
            //grab the last card from the deck, and add it to the active player's hand
            $_SESSION['players'][$i]['hand'][] = array_pop($_SESSION['deck']);
            //Recalculate their score (aces drop to 1 automatically if needed)
            $_SESSION['players'][$i]['score'] = scoreHand($_SESSION['players'][$i]['hand']);

            //********** GROUP TASK 1 (part 2) **********
            //Play "deal.wav" here too — a hit is also a card being dealt.
            //*******************************************

            $sound = "deal.wav";
            //a bust settles their bet (the stake never comes back) and ends their turn
            if ($_SESSION['players'][$i]['score'] > 21) {
                $message = "{$_SESSION['players'][$i]['name']} busted!";
                //********** GROUP TASK 2 (part 2) **********
                //A bust deserves the lose sound.
                //*******************************************
                $sound = "lose.wav";
                $_SESSION['players'][$i]['bet'] = 0;
                nextTurn();
            }
        }
    }
    
    // Stay — pass the turn along
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['stay'])) {
        $i = $_SESSION['active'];
        //staying just hands play to the next player;
        //when the last player stays, nextTurn() runs the dealer's turn
        if ($i < count($_SESSION['players']) && $_SESSION['players'][$i]['bet'] > 0) {
            nextTurn();
        }
    }

    //********** GROUP TASK 5 (part 1): Out of money? **********
    //A player with $0 can never place a bet again — they're stuck forever!
    //Check every player here: if someone is broke AND has no bet on the table,
    //have the house stake them another $100 (and add it to the message).
    //Think about it: why does the "no bet on the table" part matter?
    //What would happen without it when someone bets everything they have?
    //**********************************************************
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blackjack!</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h2>Deck:</h2>
    <div class='deck'>
        <?php
            //Loop through each card in the deck, printing it to the screen
            //We use a variable $i to offset each image a little bit to the right
            $i = 0;
            foreach($_SESSION['deck'] as $card) {
                $undealt = "<img src='img/undealt.jpg' class='undealt' style='--i:$i'>";
                $i++;
                echo $undealt;
            }
        ?>
    </div>

    <form method="post">
        <input type="text" name="pname" placeholder="Player name">
        <button type="submit" name="addplayer">Add Player</button>
    </form>

    <form method="post">
        <button type="submit" name="shuffle">Shuffle Deck</button>
    </form>
    
    <form method="post">
        <?php
            //one bet box per player — named bet[0], bet[1], ...
            //so they arrive in PHP together as the array $_POST['bet']
            foreach ($_SESSION['players'] as $i => $p) {
                $name = htmlspecialchars($p['name']);
                echo "<label>$name: $<input type='number' name='bet[$i]' value='10' min='1'></label> ";
            }
        ?>
        <button type="submit" name="start">Start Game</button>
    </form>
    
    <h2>Dealer: <?= $_SESSION['reveal'] ? $_SESSION['dscore'] : "?";?></h2>
    <div class='hand'>
        <?php
            //Show the dealer's hand
            //The first card stays face down until every player has finished
            $i = 0;
            foreach($_SESSION['dhand'] as $card) {
                if ($i == 0 && !$_SESSION['reveal']) {
                    $img = "undealt.jpg";
                } else {
                    $img = $card['card'];
                }
                $dealt = "<img src='img/$img' class='dealt'>";
                echo $dealt;
                $i++;
            }
        ?>
    </div>
    
    <?php
        //One heading and one row of cards per player, just like the dealer gets.
        //htmlspecialchars() stops a sneaky player name from injecting HTML.
        foreach ($_SESSION['players'] as $i => $p) {
            $name = htmlspecialchars($p['name']);

            //point at whoever's turn it is, and highlight their hand
            $isTurn = ($i == $_SESSION['active'] && $p['bet'] > 0);
            $turn = $isTurn ? " &larr; your turn" : "";
            $hl = $isTurn ? " active" : "";

            echo "<h2>$name &mdash; Money: \${$p['money']} &mdash; Bet: \${$p['bet']} &mdash; Score: {$p['score']}$turn</h2>";
            echo "<div class='hand$hl'>";
            foreach ($p['hand'] as $card) {
                echo "<img src='img/{$card['card']}' class='dealt'>";
            }
            echo "</div>";
        }
    ?>

    <form method="post">
        <button type="submit" name="hit">Hit Me</button>
    </form>
    
    <form method="post">
        <button type="submit" name="stay">Stay</button>
    </form>
    
    <?php
        //If the game logic above produced a message, inject a script that
        //waits for the page to finish rendering before popping the alert
        //json_encode() wraps the message in quotes and escapes any quotes inside it,
        //so apostrophes in the message can't break the JavaScript
        if ($message != "") {
            $js = json_encode($message);
            echo "<script>
                window.addEventListener('load', function() {
                    setTimeout(function() { alert($js); }, 100);
                });
            </script>";
        }

        //Same injection idea as the alert. The sound starts on load, 100ms
        //before the alert, and keeps playing while the alert is open.
        //The .catch() is there because browsers block audio until the user
        //has clicked something on the site at least once this session.
        if ($sound != "") {
            echo "<script>
                window.addEventListener('load', function() {
                    new Audio('sfx/$sound').play().catch(function() {});
                });
            </script>";
        }
    ?>
</body>
</html>
