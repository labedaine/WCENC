<?php $data = $_POST['data']; ?>
<?php
setlocale(LC_TIME, "fr_FR");
?>
<h3  class="titlePage">Groupe <?php echo $data[0]['code_groupe']; ?></h3>
<button type="button" class="btn btn-primary" id="sauvParis">Sauvegarder vos paris</button>
<table id="tabParis" class="table table-hover table-sm no-gutter">
  <tbody>
    <?php foreach ($data as $match): ?>
      <tr class="match" data-idmatch="<?php echo $match["id"];?>">
        <?php if ($match["pays1"] == ""): ?>
          <td><i class="fas fa-question"></i></td>
        <?php else: ?>
          <td><img src="ressource/img/drapeaux/drapeau-<?php echo strtolower($match["pays1"]); ?>.png"/></td>
        <?php endif; ?>
        <td><?php echo $match["pays1"]? $match["pays1"] : "Equipe inconnue"; ?></td>
        <?php if (strtotime($match["date_match"]) > strtotime('now')): ?>
          <td class="paris"><input class="inputParisDom" type="number" name="idequipe_match" data-idequipe="<?php echo $match["equipe_id_dom"];?>" data-equipe="dom" min="0" value="<?php echo $match["paris_dom"];?>"></td>
          <td>-</td>
          <td class="paris"><input class="inputParisExt" type="number" name="idequipe_match" data-idequipe="<?php echo $match["equipe_id_ext"];?>" data-equipe="ext" min="0" value="<?php echo $match["paris_ext"];?>"></td>
        <?php else: ?>
          <td class="paris"></td>
          <td class="resultat"><span class="rounded-circle"><?php echo $match["score_dom"]; ?></span>-<span class="rounded-circle"><?php echo $match["score_ext"]; ?></span></td>
          <td class="paris"></td>
        <?php endif; ?>
        <td><?php echo $match["pays2"]? $match["pays2"] : "Equipe inconnue"; ?></td>
        <?php if ($match["pays2"] == "" ):?>
          <td><i class="fas fa-question"></i></td>
        <?php else: ?>
          <td><img src="ressource/img/drapeaux/drapeau-<?php echo strtolower($match["pays2"]); ?>.png"/></td>
        <?php endif; ?>
        <td class="dateMatch"><?php echo  strftime("%A %d %B %Y à %H:%M", strtotime($match["date_match"])); ?></td>
        <th class="pointGagner perdu"></th>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

