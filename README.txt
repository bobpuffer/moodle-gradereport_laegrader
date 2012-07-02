1. Remove any pre-existing versions of the laegrader report
2. Unzip contents into grade/report (if installing from a zip archive)
3. Run Site administration->Notifications
4. It is recommended you disable teacher access to the core grader report to avoid confusion through permissions. Do not remove the core grader
    report as it screws up Moodle because Moodle is not actually all that dynamic.
5. This version contains the following functionality not available in the core grader report:
- scrolls horizontally and vertically while freezing the student columns and the grade item rows
- negates need for pages of students
- removes nested spans for handling categories and course category (along with the '+', '-' and 'o' buttons nobody seems able to find)
- operates under Firefox, Safari, Opera and IE8 and later
- includes a "Copy to Excel" button for quick dumps of report contents (formatted as seen with item maxgrades) CURRENTLY DISABLED
- allows input of letter grades which are converted to numeric values based on letter-grade setup for course CURRENTLY DISABLED
- wrapped grade item titles
- editalways user preference that displays grades editable but in their selected display type CURRENTLY DISABLED
- allows input of percentage values followed by %
- much cleaner css to straighten out the table lines for most themes
- Range and average rows (if turned on) frozen at the top, below the grade item names
- lang files are self-contained