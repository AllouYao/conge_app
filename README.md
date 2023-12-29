1) Recalculate le total des heure supplementaire.
2) Metre en place la fin des conger.
3) Appliquer une contraite sur le
4) Gestion des départs de salariés
- personal
- motifDepart
- dateEffet
- bilanFinancier
- allocation congé


I) Gestion des heures supplémentaires
- Taf Bien fait.
II) Gestion des congés
- 



    #[Route('/{uuid}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Departure $departure, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $departure->getId(), $request->request->get('_token'))) {
            $entityManager->remove($departure);
            $entityManager->flush();
        }
    
        flash()->addSuccess('Départ supprimé avec succès.');
        return $this->redirectToRoute('departure_index', [], Response::HTTP_SEE_OTHER);
    }

    if ($ancienneteYear < 1) {
    $indemniteLicenciement = 0;
    } elseif ($ancienneteYear <= 5) {
    $indemniteLicenciement = $ancienneteYear * (($salaireGlobalMoyen * 30) / 100);
    } elseif ($ancienneteYear >= 6 && $ancienneteYear <= 10) {
    $indemniteLicenciement =
    5 * (($salaireGlobalMoyen * 30) / 100) + ($ancienneteYear - 5) * (($salaireGlobalMoyen * 35) / 100);
    } elseif ($ancienneteYear > 10) {
    $indemniteLicenciement =
    5 * (($salaireGlobalMoyen * 30) / 100) + 5 * (($salaireGlobalMoyen * 35) / 100) + ($ancienneteYear - 10)
    * (($salaireGlobalMoyen * 40) / 100);
    }