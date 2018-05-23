<?php $data = $_POST['data']; ?>

<h3  class="titlePage">Groupe <?php echo $data[0]['code_groupe']; ?></h3>
<table id="tabParis" class="table table-hover table-sm no-gutter">
  <tbody>
    <?php foreach ($data as $match): ?>
    <tr>
      <td><img src="ressource/img/drapeaux/drapeau-<?php echo strtolower($match["pays1"]); ?>.png"/></td>
      <td class="col-md-3"><?php echo $match["pays1"]; ?></td>
      <?php if (1): ?>
        <td class="paris"><input type="number" name="idequipe_match" min="0"></td>
        <td>-</td>
        <td class="paris"><input type="number" name="idequipe_match" min="0"></td>
      <?php else: ?>
        <td class="paris">1</td>
        <td class="resultat"><span class="rounded-circle"><?php echo $match["score_equipe_1"]; ?></span>-<span class="rounded-circle"><?php echo $match["score_equipe_2"]; ?></span></td>
        <td class="paris">1</td>
      <?php endif; ?>
      <td class="col-md-3"><?php echo $match["pays2"]; ?></td>
      <td><img src="ressource/img/drapeaux/drapeau-<?php echo strtolower($match["pays2"]); ?>.png"/></td>
      <td class="dateMatch"><?php echo $match["date_match"]; ?></td>
      <th class="pointGagner perdu"></th>
    </tr>
  <?php endforeach; ?>
<!-- <tr>


  {"data":"Array\n(\n [0] => Array\n (\n [date_match] => 2018-06-14 17:00:00+02\n [code_equipe_1] => RUS\n [pays1] => Russie\n [score_equipe_1] => 0\n [code_groupe] => A\n [code_equipe_2] => SAU\n [pays2] => rabie Saoudite\n [score_equipe_2] => 0\n )\n\n [1] => Array\n (\n [date_match] => 2018-06-15 14:00:00+02\n [code_equipe_1] => EGY\n [pays1] => Egypte\n [score_equipe_1] => 0\n [code_groupe] => A\n [code_equipe_2] => URU\n [pays2] => Uruguay\n [score_equipe_2] => 0\n )\n\n [2] => Array\n (\n [date_match] => 2018-06-19 20:00:00+02\n [code_equipe_1] => RUS\n [pays1] => Russie\n [score_equipe_1] => 0\n [code_groupe] => A\n [code_equipe_2] => EGY\n [pays2] => Egypte\n [score_equipe_2] => 0\n )\n\n [3] => Array\n (\n [date_match] => 2018-06-20 17:00:00+02\n [code_equipe_1] => URU\n [pays1] => Uruguay\n [score_equipe_1] => 0\n [code_groupe] => A\n [code_equipe_2] => SAU\n [pays2] => rabie Saoudite\n [score_equipe_2] => 0\n )\n\n [4] => Array\n (\n [date_match] => 2018-06-25 16:00:00+02\n [code_equipe_1] => URU\n [pays1] => Uruguay\n [score_equipe_1] => 0\n [code_groupe] => A\n [code_equipe_2] => RUS\n [pays2] => Russie\n [score_equipe_2] => 0\n )\n\n [5] => Array\n (\n [date_match] => 2018-06-25 16:00:00+02\n [code_equipe_1] => SAU\n [pays1] => rabie Saoudite\n [score_equipe_1] => 0\n [code_groupe] => A\n [code_equipe_2] => EGY\n [pays2] => Egypte\n [score_equipe_2] => 0\n

      <td><img src="../ressource/img/drapeaux/drapeau-belgique.png"/></td>
      <td>Belgique</td>
      <td class="paris">3</td>
      <td class="resultat"><span class="rounded-circle">4</span>-<span class="rounded-circle">1</span></td>
      <td class="paris">1</td>
      <td>Russie</td>
      <td><img src="../ressource/img/drapeaux/drapeau-russie.png"/></td>
      <td class="dateMatch" >Mercredi 3 Avril à 16h</td>
      <th class="pointGagner perdu"><div class="coin bronze"><p>3</p></div></th>
    </tr>
    <tr>
      <td><img src="../ressource/img/drapeaux/drapeau-portugal.png"/></td>
      <td>Portugal</td>
      <td class="paris">1</td>
      <td class="resultat"><span class="rounded-circle">2</span>-<span class="rounded-circle">2</span></td>
      <td class="paris">1</td>
      <td>Espagne</td>
      <td><img src="../ressource/img/drapeaux/drapeau-espagne.png"/></td>
      <td class="dateMatch" >Mercredi 3 Avril à 16h</td>
      <th class="pointGagner perdu"><div class="coin silver"><p>5</p></div></th>
    </tr>
    <tr class="success">
      <td><img src="../ressource/img/drapeaux/drapeau-allemagne.png"/></td>
      <td>Allemagne</td>
      <td class="paris">3</td>
      <td class="resultat"><span class="rounded-circle">3</span>-<span class="rounded-circle">1</span></td>
      <td class="paris">1</td>
      <td>Argentine</td>
      <td><img src="../ressource/img/drapeaux/drapeau-argentine.png"/></td>
      <td class="dateMatch" >Mercredi 3 Avril à 16h</td>
      <th class="pointGagner perdu"><div class="coin gold"><p>8</p></div></th>
    </tr> -->

</tbody>
