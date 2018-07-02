<?php $classementIndiv = @$_POST['dataIndiv'];?>
<?php $classementPromo = @$_POST['dataPromo'];?>
<?php $classementCollec = @$_POST['dataCollec'];?>

<div class="row">
  <h3 class="titlePage"><span id="titreClassement"></span>

<i>
    <label style="font-size:15px">(Filtrer par <select id="promoSelect">
            <option value="Toutes">Toutes</option>
            <option value="PSE">PSE</option>
            <option value="ISA">ISA</option>
            <option value="ISC">ISC</option>
            <option value="CSP">CSP</option>
            <option value="TG">TG</option>
            <option value="ENSEIGNANT">ENSEIGNANT</option>
        </select>
        </label>
        )
        </i>

        </h3>
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
            <tr ligne id="<?php echo $value['id']; ?>" login="<?php echo $value['login']; ?>" class="ligneInter" data-promo="<?php echo $value['promotion']; ?>">
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



</div>
