<?php $classementIndiv = @$_POST['dataIndiv'];?>
<?php $classementPromo = @$_POST['dataPromo'];?>
<?php $classementCollec = @$_POST['dataCollec'];?>

<div class="row">
  <h3 id="titreClassement" class="titlePage"></h3>

      <table id="tabParisIndiv" class="classementTable table table-hover table-sm no-gutter" style="display:none">
        <thead  class="thead-light">
          <tr>
            <th></th>
            <th>Login</th>
            <th>Prénom</th>
            <th>Promo</th>
            <th>Points</th>
            <th>Détail/paris</th>
            <th>Paris</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($classementIndiv as $key => $value): ?>
            <tr ligne id="<?php echo $value['id']; ?>" login="<?php echo $value['login']; ?>">
              <td class="center"><?php echo $key + 1 ; ?></td>
              <td><?php echo $value['login']; ?></td>
              <td><?php echo $value['prenom']; ?></td>
              <td><?php echo $value['promotion']; ?></td>
              <td><?php echo $value['points']; ?></td>
              <td>
                  <span class="rounded-circle bg-warning text-white rondpoint"><?php echo $value['p3']; ?></span>
                  <span class="rounded-circle bg-danger text-white rondpoint"><?php echo $value['p2']; ?></span>
                  <span class="rounded-circle bg-secondary text-white rondpoint"><?php echo $value['p1']; ?></span>
              </td>
              <td><button type="button" class="btn btn-primary">Voir ses paris terminés</button></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>



      <table id="tabParisColl" class="classementTable table table-hover table-sm no-gutter"  style="display:none">
        <tbody>
          <tr>
            <th></th>
            <th>Promo</th>
            <th>Points</th>
            <th>Nb paris</th>
            <th>Moyenne points/paris</th>
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



      <div id="tabParisPromo">
      <select id="promoSelect">
        <option value="1">PSE</option>
        <option value="2">ISA</option>
        <option value="3">ISC</option>
        <option value="4">CSP</option>
        <option value="5">TG</option>
        <option value="6">ENSEIGNANT</option>
      </select>
      <table id="tabParisPromo" class="classementTable table table-hover table-sm no-gutter">
        <thead>
          <tr>
            <th></th>
            <th>Login</th>
            <th>Prénom</th>
            <th>Promo</th>
            <th>Points</th>
            <th>Paris</th>
          </tr>
        </thead>
        <?php foreach ($classementPromo as $numPromo => $promo): ?>
          <?php foreach ($promo as $key => $value): ?>
            <tr ligne id="<?php echo $value['id']; ?>" login="<?php echo $value['login']; ?>" class="ligneInter" data-promo="<?php echo $numPromo; ?>">
              <td><?php echo $key + 1; ?></td>
              <td><?php echo $value['login']; ?></td>
              <td><?php echo $value['prenom']; ?></td>
              <td><?php echo $value['promotxt']; ?></td>
              <td><?php echo $value['points']; ?></td>
              <td><button type="button" class="btn btn-primary">Voir ses paris terminés</button></td>
            </tr>
          <?php endforeach; ?>
        <?php endforeach; ?>
      </table>
    </div>
</div>
