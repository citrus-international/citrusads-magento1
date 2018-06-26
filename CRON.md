##ADDING A NEW MAGENTO CRON TASK VIA SSH/TERMINAL

#Run:

crontab -e 

#At this point you will see a text editor, where you can add or edit cron tasks. Starting from a new line, add the following record:

* * * * * sh /path/to/your/magento/site/root/cron.sh

Again, don’t forget to insert your own default folder path!

#Save the changes and close the file.

#If you did everything correctly, run:
crontab -l 

This command will show you the newly created task.
