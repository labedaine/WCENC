<?php $data = $_POST['data']; ?>

<h3  class="titlePage">Groupe <?php echo $data[0]['code_groupe']; ?></h3>
<button type="button" class="btn btn-primary" id="sauvParis">Sauvegarder vos paris</button>
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
          <td class="resultat"><span class="rounded-circle"><?php echo $match["score_dom"]; ?></span>-<span class="rounded-circle"><?php echo $match["score_ext"]; ?></span></td>
          <td class="paris">1</td>
        <?php endif; ?>
        <td class="col-md-3"><?php echo $match["pays2"]; ?></td>
        <td><img src="ressource/img/drapeaux/drapeau-<?php echo strtolower($match["pays2"]); ?>.png"/></td>
        <td class="dateMatch"><?php echo $match["date_match"]; ?></td>
        <th class="pointGagner perdu"></th>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
