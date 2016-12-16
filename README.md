# LOTTERY-CODES
LOTTERY-CODES is a solution for generating and verifing lottery codes using PHP! With it, you can generate and sent codes to users without saved them, and verify the codes just by logical calculation.

## Demand and Inspiration
<p>We were running a project, which need generate great amount codes like "TCLHN9PE4CNC" and saved them to database for prepaire to be verifed. So, we used much storage to save codes after be
benerated.</p>
<p>When we have a chance to refactor our project, we decision to improve the situation. I tried to find the possibility of generate & verify codes by encryption algorithm, to avoid keeping all the
generated codes in hard disk. Actually, a code can convert to many bin messages, which can be splited to several fixed parts. So, I can put any msg in one code, even the symmetric encrypt message
which can improve the code's security.</p>

## Embryonic
<p>Tried and tried again, I splite the bins to three parts: batch ID, codes number and encrypt message. With these message, I can generate codes by batches, each batch should generate several
codes of specific quantity and each code has fixed numbers, and the remaining bins to save symmetric encrypted sign.</p>

#### Flow chart for generate:
![Flow chart for generate](images/generate.jpg)

#### Flow chart for verify:
![Flow chart for verify](images/verify.jpg)

## Problems and efforts

 - At first, I want to use **base 64** whose dictionary contains [a-zA-Z0-9\-\_], But the codes as hard to read and input; 
 - Then **base 32** was believed to be suitable which contains [A-Z0-9] without "0O1I" to avoid ambiguity as 32 charactors;
 - But the productors prefer to remove more charactors to avoid ambiguity, so **base 23** was decided at last.

#### achieve for base 23
<p>Base 32 meant 5 binaries as one charactor, base 16 meant 4 binaries as ont charactor which use only 16 charactors but representative less binaries for one code, So I tried to think about some hex
meant 4.5 binaries as one charactor. 2^4.5≈22.6, so we can consider it as base 23. And we can remove the other 9 charactors as '8B2Z5SDUV'.</p>

#### Advanced
save as redis
