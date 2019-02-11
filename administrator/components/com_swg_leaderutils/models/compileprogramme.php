<?php
require_once JPATH_SITE . '/swg/swg.php';

class SWG_LeaderUtilsModelCompileProgramme extends JModelItem
{
    public function getProgramme()
    {
        // Default is next programme (TODO)
        return WalkProgramme::get(WalkProgramme::getNextProgrammeId());
    }
    
    public function getProposals()
    {
        $factory = new WalkProposalFactory();
        $factory->startProgramme = WalkProgramme::getNextProgrammeId(); // TODO
        return $factory->get();
    }
}
