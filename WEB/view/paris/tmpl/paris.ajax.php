
<?php $data = $_POST['data']; ?>
<?php
setlocale(LC_TIME, "fr_FR");
?>
<h3  class="titlePage">Liste des matchs</h3>
<div style="position:fixed;right:35px;top:100px;z-index:999;margin:3px;background:#E8E9EB;border-radius:10px;">
    <button type="button" class="btn btn-primary" id="sauvParis" >Sauvegarder vos paris</button>
</div>
<table id="tabParis" class="table table-hover table-sm no-gutter">
  <tbody>
    <?php foreach ($data as $match): ?>
      <tr etat="<?php echo $match["etat_id"];?>">
          <td colspan="8" style="text-align:left; padding:0px;border-top:0px">
              <span ><?php echo $match["etat"]; ?></span>
              <span class="biseauteDate"><?php echo strftime("%A %d %B %Y à %H:%M", strtotime($match["date_match"])); ?></span>
          </td>
          <td colspan="2" style="text-align:center; padding:0px;border-top:0px">
              <span class="resultat">Résultat</span>
              <span class="points">Points</span>
          </td>
      </tr>
      <tr class="match"  data-idmatch="<?php echo $match["id"];?>">
          <td>
              <?php if($match["groupe1"]): ?>
              <span style="font-size:10px">groupe</span><br/><?php echo $match["groupe1"]; ?></td>
              <?php endif; ?>
          </td>
        <?php if ($match["pays1"] == ""): ?>
          <td><i class="fas fa-question"></i></td>
        <?php else: ?>
          <td><img src="ressource/img/drapeaux/drapeau-<?php echo strtolower($match["pays1"]); ?>.png"/></td>
        <?php endif; ?>
        <td><?php echo $match["pays1"]? $match["pays1"] : "Equipe inconnue"; ?></td>
        <?php if(in_array($match["etat_id"], array(1,2))): ?>
          <td class="paris"><input class="inputParisDom" type="number" name="idequipe_match" data-idequipe="<?php echo $match["equipe_id_dom"];?>" data-equipe="dom" min="0" value="<?php echo $match["paris_dom"];?>"></td>
          <td>-</td>
          <td class="paris"><input class="inputParisExt" type="number" name="idequipe_match" data-idequipe="<?php echo $match["equipe_id_ext"];?>" data-equipe="ext" min="0" value="<?php echo $match["paris_ext"];?>"></td>
        <?php else: ?>
          <td class="paris"><?php echo $match["paris_dom"];?></td>
          <td>-</td>
          <td class="paris"><?php echo $match["paris_ext"];?></td>
        <?php endif; ?>
        <td><?php echo $match["pays2"]? $match["pays2"] : "Equipe inconnue"; ?></td>
        <?php if ($match["pays2"] == "" ):?>
          <td><i class="fas fa-question"></i></td>
        <?php else: ?>
          <td><img src="ressource/img/drapeaux/drapeau-<?php echo strtolower($match["pays2"]); ?>.png"/></td>
        <?php endif; ?>
        <td class="tdResultat">
            <?php if($match["etat_id"] == 6): ?>
            <span class="rounded-circle"><?php echo $match["score_dom"]; ?></span>-<span class="rounded-circle"><?php echo $match["score_ext"]; ?></span>
            <?php endif; ?>
        </td>
        <td class="pointGagner">
            <?php if ($match["points_acquis"]):?>
            <?php echo $match["points_acquis"]; ?>
            <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

