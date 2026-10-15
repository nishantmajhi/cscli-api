factorial(0, 1) :- !.
factorial(N, F) :-
    N > 0,
    N1 is N - 1,
    factorial(N1, F1),
    F is N * F1.

main :-
    write('Enter a number: '),
    read(N),
    ( N < 0
    -> write('Factorial of negative number can\'t be calculated!'), nl
    ;  factorial(N, F),
       format('Factorial of ~w = ~w~n', [N, F])
    ).

:- initialization(main).
