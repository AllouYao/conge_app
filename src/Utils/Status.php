<?php

namespace App\Utils;

final class Status
{
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
    public const CONCUBIN = 'CONCUBIN';
    public const CELIBATAIRE = 'CELIBATAIRE';
    public const DIVORCE = 'DIVORCE';
    public const MARIEE = 'MARIE';
    public const SEPARE = 'SEPARE';
    public const VEUF = 'VEUF';

    /**
     * Diplome
     */
    public const BAC = 'BAC';
    public const LICENCE = 'LICENCE';
    public const MASTER_1 = 'MASTER 1';
    public const MASTER_2 = 'MASTER 2';
    public const DOCTORAL = 'DOCTORAL';

    /**
     * Niveau de formation
     */
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
    public const TEMPS_PARTIEL = 'TEMPS_PARTIEL';

    /**
     * Prime non juridique
     */
    public const PRIME_PANIER = 'PRIME_PANIER';

    public const PRIME_SALISSURE = 'PRIME_SALISSURE';

    public const PRIME_TENUE_TRAVAIL = 'PRIME_TENUE_TRAVAIL';

    public const PRIME_OUTILLAGE = 'PRIME_OUTILLAGE';

    /**
     * Type Congés
     */
    public const CONGE_GLOBAL = 'CONGES_GLOBAL';
    public const CONGE_MATERNITY = 'CONGE_MATERNITY';

    /**
     * Salaire horraire
     */

    public const SALAIRE_HORRAIRE_CATEGORIEL = 75000/173.33;

    /**
     * Taux horraire
     */
    
    public const TAUX_JOUR_OUVRABLE = (15/100);
    public const TAUX_JOUR_OUVRABLE_EXTRA = (50/100);
    public const TAUX_NUIT_OUVRABLE_OR_NON_OUVRABLE = (75/100); 
    public const TAUX_NUIT_NON_OUVRABLE = (100 / 100);


    /**
     * Jour
     */
    public const JOUR = "JOUR";
    public const NUIT = "NUIT";
    public const NORMAL = "NORMAL";
    public const DIMANCHE_FERIE = "DIMANCHE/FÉRIÉ";
    
    public const TYPE_ABSENCE = [
                                "CONVENANCES PERSONNELLES",
                                "MALADIE","ACCIDENT DE TRAVAIL", "ACTIVITÉ SYNDICALE",
                                "PREMIÈRE COMMUNION (1 OUR)","NAISSANCE D’UN ENFANT (2)",
                                "MARIAGE D’UN ENFANT, D’UN FRÈRE, D’UNE SŒUR (2 JOURS)",
                                "MARIAGE DU TRAVAILLEUR (4 JOURS)","DÉMÉNAGEMENT (1 JOUR)",
                                "DÉCÈS D’UN ENFANT, PÈRE OU MÈRE (4 JOURS)",
                                "DÉCÈS D’UN FRÈRE OU D’UNE SŒUR (2 JOURS)","BAPTÊME D’UN ENFANT",
                                "CONGÉ DE MATERNITÉ", "DÉCÈS DU CONJOINT (5 JOURS)", 
                                "DÉCÈS PEAU PÈRE OU BELLE-MÈRE (2 JOURS)"];


    
    


    
}