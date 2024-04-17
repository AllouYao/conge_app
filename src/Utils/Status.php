<?php

namespace App\Utils;

final class Status
{
    /** Type Conges & ode de paiement conges */
    public const ULTERIEUR = 'Ultérieur';
    public const IMMEDIAT = 'Immédiat';
    public const EFFECTIF = 'Effectif';
    public const PARTIEL = 'Partiel';
    public const IMPAYEE = 'IMPAYEE';

    /** Category salarie */
    public const OUVRIER_EMPLOYE = 'Ouvriers / Employés';
    public const CHAUFFEUR = 'Chauffeurs';
    public const AGENT_DE_MAITRISE = 'Agents de maitrise';
    public const CADRE = 'Cadres';

    /**
     * Identity Document
     */
    public const CNI = 'CNI';
    public const ATTESTATION = 'ATTESTATION';
    public const PASSPORT = 'PASSPORT';
    public const CARTE_CONSULAIRE = 'CARTE CONSULAIRE';

    /**
     * Sex
     */
    public const FEMININ = 'FEMME';
    public const MASCULIN = 'HOMME';

    /**
     * Mode Paiement
     */
    public const CAISSE = 'CAISSE';
    public const VIREMENT = 'VIREMENT';
    public const CHEQUE = 'CHEQUE';

    /**
     * Etat Civil
     */
    public const CELIBATAIRE = 'CELIBATAIRE';
    public const DIVORCE = 'DIVORCE';
    public const MARIEE = 'MARIE';
    public const VEUF = 'VEUF';


    /**
     * Niveau de formation
     */
    public const BAC = 'BAC';

    public const BTS = 'BTS (Bac +2)';
    public const MAITRISE = 'MAITRISE (Bac + 4)';
    public const Master = 'MASTER (Bac + 5)';

    /**
     * Type de contrat
     */
    public const CDD = 'CDD';
    public const  CDI = 'CDI';
    public const  CDDI = 'CDDI';
    public const STAGE = 'STAGE';
    public const OCCASIONNEL = 'OCCASIONNEL';

    /**
     * Type Temps Contractuel
     */
    public const TEMPS_PLEIN = 'TEMPS PlEIN';
    public const TEMPS_PARTIEL = 'TEMPS PARTIEL';

    /**
     * Prime non juridique
     */
    public const PRIME_PANIER = 'PRIME PANIER';

    public const PRIME_SALISSURE = 'PRIME SALISSURE';

    public const PRIME_TENUE_TRAVAIL = 'PRIME TENUE TRAVAIL';

    public const PRIME_OUTILLAGE = 'PRIME OUTILLAGE';
    public const GRATIFICATION = 'GRATIFICATION';
    public const TRANSPORT_NON_IMPOSABLE = 'PRIME DE TRANSPORT';

    public const PRIME_RENDEMENT = 'PRIME DE RENDEMENT';
    public const PRIME_TRANSPORT = 'PRIME DE TRANSPORT';

    /**
     * Prime juridique
     */
    public const PRIME_FONCTION = 'PRIME DE FONCTION';
    public const PRIME_LOGEMENT = 'PRIME DE LOGEMENT';
    public const INDEMNITE_LOGEMENTS = 'INDEMNITE DE LOGEMENTS';
    public const INDEMNITE_FONCTION = 'INDEMNITE DE FONCTION';

    /**
     * Type Congés
     */
    public const CONGE_GLOBAL = 'CONGES GLOBAL';
    public const CONGE_MATERNITY = 'CONGE MATERNITY';

    /**
     * Salaire horraire
     */

    public const TAUX_HEURE = 173.33;

    /**
     * Taux horraire
     */

    public const TAUX_JOUR_OUVRABLE = 115 / 100;
    public const TAUX_JOUR_OUVRABLE_EXTRA = 150 / 100;
    public const TAUX_NUIT_OUVRABLE_OR_NON_OUVRABLE = 175 / 100;
    public const TAUX_NUIT_NON_OUVRABLE = 200 / 100;


    /**
     * Jour
     */
    public const JOUR = "JOUR";
    public const NUIT = "NUIT";
    public const NORMAL = "NORMAL";
    public const DIMANCHE_FERIE = "DIMANCHE/FÉRIÉ";

    public const TYPE_ABSENCE = [
        "CONVENANCES PERSONNELLES",
        "MALADIE", "ACCIDENT DE TRAVAIL", "ACTIVITÉ SYNDICALE",
        "PREMIÈRE COMMUNION (1 OUR)", "NAISSANCE D’UN ENFANT (2)",
        "MARIAGE D’UN ENFANT, D’UN FRÈRE, D’UNE SŒUR (2 JOURS)",
        "MARIAGE DU TRAVAILLEUR (4 JOURS)", "DÉMÉNAGEMENT (1 JOUR)",
        "DÉCÈS D’UN ENFANT, PÈRE OU MÈRE (4 JOURS)",
        "DÉCÈS D’UN FRÈRE OU D’UNE SŒUR (2 JOURS)", "BAPTÊME D’UN ENFANT",
        "CONGÉ DE MATERNITÉ", "DÉCÈS DU CONJOINT (5 JOURS)",
        "DÉCÈS PEAU PÈRE OU BELLE-MÈRE (2 JOURS)"
    ];

    public const REASON_DEPARTURE = [
        "DEMISSION",
        "RETRAITE",
        "LICENCIEMENT",
        "ABANDON DE POST",
        "MALADIE",
        "DECES"
    ];

    /**
     * Reason of departure
     */
    public const DEMISSION = 'DEMISSION';
    public const RETRAITE = 'RETRAITE';
    public const LICENCIEMENT_COLLECTIF = 'LICENCIEMENT COLLECTIF';
    public const LICENCIEMENT_FAUTE_LOURDE = 'LICENCIEMENT FAUTE LOURDE';
    public const LICENCIEMENT_FAIT_EMPLOYEUR = 'LICENCIEMENT DU FAIT DE EMPLOYEUR';
    public const ABANDON_DE_POST = 'ABANDON DE POST';
    public const MALADIE = 'MALADIE';
    public const DECES = 'DECES';

    /**
     * Type de charge
     */
    public const PERSONAL_CHARGE = 'CHARGE SALARIALE';
    public const FISCALE_CHARGE = 'CHARGES FISCALES';
    public const SOCIALE_CHARGE = 'CHARGES SOCIALES';
    public const EMPLOYER_CHARGE = 'CHARGE PATRONNALE';

    /**
     * Retenue forfetaire
     */
    public const ASSURANCE_FAMILLE = 'ASSURANCE_SANTE_FAMILLE_SALARIALE';
    public const ASSURANCE_CLASSIC = 'ASSURANCE_SANTE_CLASSIQUE_SALARIALE';

    /** Site de travail */
    public const STATION_AP_MAGIC = 'STATION AP MAGIC';
    public const DIRECTION = 'DIRECTION';
    public const STATION_SHELL_TREICH_HABITAT = 'STATION SHELL TREICHVILLE HABITAT';
    public const SHELL_PARIS = 'SHELL PARIS';
    public const STATION_AP_BENSON = 'STATION AP BENSON';
    public const STATION_PO_SONGON = 'STATION PO SONGON';
    public const STATION_SHELL_RO_GABON = 'STATION SHELL RO GABON';
    public const STATION_IW_YOPOUGON = 'STATION IW YOPOUGON ';
    public const BOUTIQUE_AGBOVILLE = 'BOUTIQUE AGBOVILLE';
    public const SHOP_MANAGER = 'SHOP MANAGER';
    public const BOUTIQUE_SHELL_ADZOPE = 'BOUTIQUE SHELL ADZOPE';
    public const BOUTIQUE_TREICHVILLE_HABITAT = 'BOUTIQUE TREICHVILLE HABITAT';
    public const STATION_SHELL_AGBOVILLE = 'STATION SHELL AGBOVILLE';
    public const STATION_PO_LOCODJORO = 'STATION PO LOCODJORO';
    public const STATION_SHELL_LAGUNAIRE = 'STATION SHELL LAGUNAIRE';
    public const SS_RO_GABON = 'SS RO GABON';
    public const STATION_SHELL_ADZOPE = 'STATION SHELL ADZOPE';
    public const SS_LAGUNAIRE = 'SS LAGUNAIRE';
    public const BOUTIQUE_PO_LOCODJORO = 'BOUTIQUE  PO LOCODJORO';
    public const SEA_TOURNANT_ABIDJAN_SUD = 'SEA TOURNANT-ABIDJAN SUD';


    /** Status de campagne */
    public const EN_COURS = 'EN COURS';
    public const TERMINER = 'TERMINER';
    public const EN_ATTENTE = 'EN ATTENTE';
    public const VALIDATED = 'VALIDEE';

    /** Status Operation */
    public const REMBOURSEMENT = 'REMBOURSEMENT';
    public const RETENUES = 'RETENUES';
    const PRET = 'PRET';
    const ACOMPTE = 'ACOMPTE';


    /** Statut des fonctions */
    public const COMMERCIAL_PISTE = 'COMMERCIAL PISTE';
    public const COMMERCIAL_BOUTIQUE = 'COMMERCIAL BOUTIQUE';
    public const QHM = 'QHM';
    public const SITE_MANAGER = 'SITE MANAGER';
    public const LAVEUR = 'LAVEUR';
    public const SEA = 'SEA';
    public const OS = 'OS';
    public const RH = 'RESOURCE HUMAINE';
    public const ASSISTANT_RH = 'ASSISTANT RH';
    public const TRESORERIE = 'TRESORERIE';
    public const ASSISTANCE_TR = 'ASSISTANT TRESORERIE';
    public const GERANTE = 'GERANTE';
    public const RESPONSABLE_SO = 'RESPONSABLE OPERATION';
    public const ASSISTANT_SO = 'ASSISTANT SERVICE OPERATION';
    public const ESCORTE = 'ESCORTE';
    public const SUPERVISEUR = 'SUPERVISEUR';
    public const RMG = 'RESPONSABLE DES MOYENS GENERAUX';
    public const COMPTABLE = 'COMPTABLE';
    public const ASSISTANT_COMT = 'ASSISTANT COMPTABLE';

    /** Type heure supplémentaire */
    public const MAJORATION_15_PERCENT = 'MAJORATION_15_PERCENT';
    public const MAJORATION_50_PERCENT = 'MAJORATION_50_PERCENT';
    public const MAJORATION_75_PERCENT = 'MAJORATION_75_PERCENT';
    public const MAJORATION_100_PERCENT = 'MAJORATION_100_PERCENT';

    public const SUPPLEMENTAIRE = 'SUPPLEMENTAIRE';
    public const PAYE = 'PAYE';
    public const PENDING = 'PENDING';
    public const CANCELED = 'CANCELED';
    public const REFUND = 'REFUND';
    public const REASONCODE =  [
        'demission' => Status::DEMISSION,
        'retraite' => Status::RETRAITE,
        'licenciement_lourde' => Status::LICENCIEMENT_FAUTE_LOURDE,
        'licenciement_simple' => Status::LICENCIEMENT_FAUTE_LOURDE,
        'deces' => Status::DECES
    ];


}