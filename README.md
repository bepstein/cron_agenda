# cron_agenda
Goes through the system directories and enumerates all cron jobs on the system.  Gives you an agenda for the day.

I borrowed some code from this Stack Overflow discussion.  Thanks to the user diyism.

http://stackoverflow.com/questions/321494/calculate-when-a-cron-job-will-be-executed-then-next-time/28242522

Here is a sample from my laptop:

[2]ep:~/ep/code/cron_agenda$ date
Sun Feb  1 22:41:22 EST 2015
[2]ep:~/ep/code/cron_agenda$ sudo php cron_agenda.php | head -20
0:00    */10 * * * *    root    /usr/lib64/sa/sa1 1 1
0:00    0 0 1 feb *     ep      /usr/bin/date > /dev/null 2>&1
0:00    0 0 * * *       root    /bin/date > /dev/null
0:01    01 * * * *      root    run-parts /etc/cron.hourly
0:01    01 * * * *      root    /etc/cron.hourly/0anacron
0:01    01 * * * *      root    /etc/cron.hourly/mcelog.cron
0:10    */10 * * * *    root    /usr/lib64/sa/sa1 1 1
0:20    */10 * * * *    root    /usr/lib64/sa/sa1 1 1
0:30    */10 * * * *    root    /usr/lib64/sa/sa1 1 1
0:40    */10 * * * *    root    /usr/lib64/sa/sa1 1 1
0:50    */10 * * * *    root    /usr/lib64/sa/sa1 1 1
1:00    0 1 * * Sun     root    /usr/sbin/raid-check
1:00    */10 * * * *    root    /usr/lib64/sa/sa1 1 1
1:01    01 * * * *      root    run-parts /etc/cron.hourly
1:01    01 * * * *      root    /etc/cron.hourly/0anacron
1:01    01 * * * *      root    /etc/cron.hourly/mcelog.cron
1:10    */10 * * * *    root    /usr/lib64/sa/sa1 1 1
1:20    */10 * * * *    root    /usr/lib64/sa/sa1 1 1
1:30    */10 * * * *    root    /usr/lib64/sa/sa1 1 1
1:40    */10 * * * *    root    /usr/lib64/sa/sa1 1 1
....

The first column represents the time of the day in 23:59 format.
The second column is the frequency that is being used by cron for the job.
The third column is the user it is running as.
The fourth column is the command being run.

You can see it also expands the run-parts directories.  Like at 0:01, it calls
"run-parts /etc/cron.hourly".  The next two 0:01 scripts are in the /etc/cron.hourly
directory.

The script automatically checks /etc/crontab, /etc/cron.d and /var/spool/cron
as its sources.
