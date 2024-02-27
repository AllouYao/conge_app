<?php

namespace App\Utils;

final class Status
{
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
    public const FEMININ = 'FEMININ';
    public const MASCULIN = 'MASCULIN';

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

    /** Status de campagne */
    public const EN_COURS = 'EN COURS';
    public const TERMINER = 'TERMINER';
}