-- Update institute branding in previously saved question-paper templates.
USE courseplan;
SET NAMES latin1;

UPDATE papers
SET paper = REPLACE(
    REPLACE(
      REPLACE(paper, 'Anil Neerukonda Institute of Technology and Sciences', 'St Joseph Engineering College'),
      'ANITS',
      'SJEC'
    ),
    'An Autonomous Institute Affiliated to Andhra University, Visakhapatnam',
    'An Autonomous Institute'
)
WHERE paper LIKE '%ANITS%' OR paper LIKE '%Anil Neerukonda Institute of Technology and Sciences%';
