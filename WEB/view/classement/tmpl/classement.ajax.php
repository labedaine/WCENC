<?php $classementIndiv = $_POST['dataIndiv'];?>
<?php $classementPromo = $_POST['dataPromo'];?>
<?php $classementCollec = $_POST['dataCollec'];?>

<div class="row">
  <div class="col-6">
    <div class="col-11">
    <h3  class="titlePage">Classement Individuel</h3>
    <table id="tabParisIndiv" class="table table-hover table-sm no-gutter">
      <thead  class="thead-light">
        <tr>
          <th></th>
          <th>Login</th>
          <th>Prénom</th>
          <th>Promo</th>
          <th>Points</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($classementIndiv as $key => $value): ?>
          <tr>
            <td><?php echo $key + 1 ; ?></td>
            <td><?php echo $value['login']; ?></td>
            <td><?php echo $value['prenom']; ?></td>
            <td><?php echo $value['promotion']; ?></td>
            <td><?php echo $value['points']; ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  </div>
  <div class="col-6">
    <h3  class="titlePage">Classement Collectif</h3>
    <table id="tabParis" class="table table-hover table-sm no-gutter">
      <tbody>
        <tr>
          <th></th>
          <th>Promo</th>
          <th>Points</th>
          <th>Nb parieur</th>
          <th>Moyenne points/joueurs</th>
        </tr>
        <?php foreach ($classementCollec as $key => $value): ?>
          <tr>
            <td><?php echo $key + 1; ?></td>
            <td><?php echo $value['promotion']; ?></td>
            <td><?php echo $value['total']; ?></td>
            <td><?php echo $value['nb']; ?></td>
            <td><?php echo $value['moyenne']; ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <h3  class="titlePage">Classement Inter-Promo</h3>
    <select id="promoSelect">
      <option value="1">PSE</option>
      <option value="2">ISA</option>
      <option value="3">ISC</option>
      <option value="4">CSP</option>
      <option value="5">TG</option>
      <option value="6">ENSEIGNANT</option>
    </select>
    <table id="tabParis" class="table table-hover table-sm no-gutter">
      <tbody>
        <tr>
          <th></th>
          <th>Login</th>
          <th>Prénom</th>
          <th>Promo</th>
          <th>Points</th>
        </tr>
      </tbody>
      <?php foreach ($classementPromo as $numPromo => $promo): ?>
        <?php foreach ($promo as $key => $value): ?>
          <tr class="ligneInter" data-promo="<?php echo $numPromo; ?>">
            <td><?php echo $key + 1; ?></td>
            <td><?php echo $value['login']; ?></td>
            <td><?php echo $value['prenom']; ?></td>
            <td><?php echo $value['promotxt']; ?></td>
            <td><?php echo $value['points']; ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </table>
  </div>
</div>
