
<?php $data = $_POST['data']; ?>
<?php
setlocale(LC_TIME, "fr_FR");
?>
<h3 id="titreListeMatch" class="titlePage">Liste des matchs</h3>
<div id="containerSauvParis" style="position:fixed;right:35px;top:170px;z-index:999;margin:3px;background:#E8E9EB;border-radius:10px;">
    <button type="button" class="btn btn-primary" id="sauvParis" >Sauvegarder vos paris</button>
</div>
<div class="col-lg-11 col-xs-12" id="tabParis">
    <?php foreach ($data as $match): ?>
    <div class="form-group row entete" etat="<?php echo $match["etat_id"];?>" past="<?php echo $match["past"];?>">
        <div class="col-sm-9 col-xs-9">
             <span ><?php echo $match["etat"]; ?></span>
             <span class="biseauteDate"><?php echo strftime("%A %d %B %Y à %H:%M", strtotime($match["date_match"])); ?></span>
        </div>
        <div class="col-sm-3 col-xs-3 center">
            <span class="resultat">Résultat</span>
            <span class="points">Points</span>
        </div>
    </div>
    <div class="form-group row match my-auto center" data-idmatch="<?php echo $match["id"];?>"  past="<?php echo $match["past"];?>">
        <div class="col-sm-1 col-xs-1 drapeau" style="order: 1">
             <?php if($match["groupe1"]): ?>
              <span style="font-size:10px">groupe</span><br/><?php echo $match["groupe1"]; ?></td>
              <?php endif; ?>
        </div>
        <div class="col-sm-2 col-xs-2 my-auto center drapeau" style="order: 2">
            <?php if ($match["pays1"] == ""): ?>
            <i class="fas fa-question"></i>
            <?php else: ?>
            <img src="ressource/img/drapeaux/<?php echo strtolower($match["equipe_id_dom"]); ?>.png"/>
            <?php endif; ?>
        </div>
        <div class="col-sm-1 col-xs-1 my-auto center" col="eq1" style="order: 3" fini="<?php echo in_array($match["etat_id"], array(1,2)) ? "0" : "1"; ?>">
            <?php echo $match["pays1"]? $match["pays1"] : "Equipe inconnue"; ?>
        </div>
        <div class="col-sm-2  col-xs-2 center my-auto" col="pari" style="order: 4" fini="<?php echo in_array($match["etat_id"], array(1,2)) ? "0" : "1"; ?>">
            <?php if(in_array($match["etat_id"], array(1,2))): ?>
              <!-- <td class="paris"> --><input class="inputParisDom" type="number" name="idequipe_match" data-idequipe="<?php echo $match["equipe_id_dom"];?>" data-equipe="dom" min="0" value="<?php echo $match["paris_dom"];?>"></td>
              -
              <!-- <td class="paris"> --><input class="inputParisExt" type="number" name="idequipe_match" data-idequipe="<?php echo $match["equipe_id_ext"];?>" data-equipe="ext" min="0" value="<?php echo $match["paris_ext"];?>"></td>
            <?php else: ?>
              <!-- <td class="paris"> --><?php echo $match["paris_dom"];?></td>
              -
              <!-- <td class="paris"> --><?php echo $match["paris_ext"];?></td>
            <?php endif; ?>
        </div>
        <div class="col-sm-1 col-xs-1 my-auto center" col="eq2" style="order: 5" fini="<?php echo in_array($match["etat_id"], array(1,2)) ? "0" : "1"; ?>">
            <?php echo $match["pays2"]? $match["pays2"] : "Equipe inconnue"; ?>
        </div>
        <div class="col-sm-2 col-xs-1 my-auto center drapeau" style="order: 6">
            <?php if ($match["pays2"] == "" ):?>
              <i class="fas fa-question"></i>
            <?php else: ?>
              <img src="ressource/img/drapeaux/<?php echo strtolower($match["equipe_id_ext"]); ?>.png"/>
            <?php endif; ?>
        </div>
        <div class="col-sm-1 col-xs-1 tdResultat my-auto center" style="order: 7" fini="<?php echo in_array($match["etat_id"], array(1,2)) ? "0" : "1"; ?>">
            <?php if($match["etat_id"] == 6): ?>
            <span class="rounded-circle"><?php echo $match["score_dom"]; ?></span>&nbsp;-&nbsp;<span class="rounded-circle"><?php echo $match["score_ext"]; ?></span>
            <?php endif; ?>
        </div>
        <div class="col-sm-1 col-xs-1 pointGagner my-auto center" style="order: 8" fini="<?php echo in_array($match["etat_id"], array(1,2)) ? "0" : "1"; ?>">
            <?php if ($match["points_acquis"]):?>
            <?php echo $match["points_acquis"]; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
</div>
