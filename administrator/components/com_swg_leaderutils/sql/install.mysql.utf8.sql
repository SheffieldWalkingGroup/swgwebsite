CREATE TABLE walkproposal (
    proposal_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    leader_id SMALLINT UNSIGNED NOT NULL,
    programme_id SMALLINT UNSIGNED NOT NULL,
    walk_id SMALLINT UNSIGNED NOT NULL,
    timing_transport TEXT,
    backmarker_id SMALLINT UNSIGNED DEFAULT NULL,
    comments TEXT,
    walkinstance_id SMALLINT UNSIGNED DEFAULT NULL,
    last_updated TIMESTAMP,
    PRIMARY KEY (proposal_id),
    INDEX index_leader (leader_id),
    FOREIGN KEY (leader_id) REFERENCES walkleaders(ID),
    FOREIGN KEY (programme_id) REFERENCES walksprogramme(SequenceID),
    FOREIGN KEY (walk_id) REFERENCES walks(ID),
    FOREIGN KEY (walkinstance_id) REFERENCES walkprogrammewalks(SequenceID)
);

CREATE TABLE walkproposaldate(
    proposal_id INT UNSIGNED NOT NULL,
    walk_date DATE,
    availability TINYINT NOT NULL,
    PRIMARY KEY (proposal_id, walk_date),
    INDEX index_proposal_id (proposal_id),
    FOREIGN KEY (proposal_id) REFERENCES walkproposal(proposal_id)
);
