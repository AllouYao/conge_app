Pour activer mon server Mysql:
- docker compose up -d

/** Service pour le calcule des impôts sur salaire du salarié et aussi celui dû par l'employeur */
$salary->chargePersonal($personal);
$salary->chargeEmployeur($personal);
'charge_personals.amountIts as charge_personal_its',
'charge_personals.amountCNPS as charge_personal_cnps',
'charge_personals.amountCMU as charge_personal_cmu',
'charge_personals.AmountTotalChargePersonal as total_charge_personal',
'charge_personals.numPart as charge_personal_nombre_part',
'charge_employeurs.amountIS as charge_employeur_is',
'charge_employeurs.amountFDFP as charge_employeur_fdfp',
'charge_employeurs.amountCR as charge_employeur_cr',
'charge_employeurs.amountPF as charge_employeur_pf',
'charge_employeurs.amountAT as charge_employeur_at',
'charge_employeurs.amountCMU as charge_employeur_cmu',
'charge_employeurs.totalRetenuCNPS as total_retenu_cnps',
'charge_employeurs.totalChargeEmployeur as total_charge_employeur',