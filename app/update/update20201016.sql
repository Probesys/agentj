/** Set mailingList flag on msgs table
 * Author:  Probesys <support.agentj@probesys.com>
 * Created: 16 oct. 2020
 */ 

update msgs m
inner join maddr m2 on m2.id = m.sid
set m.ml = 'Y'
where LOWER(REGEXP_SUBSTR(m.from_addr , '([a-zA-Z0-9._%+\-]+)@([a-zA-Z0-9.-]+)\.([a-zA-Z]{2,4})')) != m2.email and spam_level>0 and spam_level < 21