program factorial
    implicit none
    integer :: num, i
    integer(kind=8) :: product

    print *, "Enter a number:"
    read *, num

    if (num < 0) then
        print *, "Factorial of negative number can't be calculated!"
        stop
    end if

    product = 1
    do i = num, 2, -1
        product = product * i
    end do

    print *, "Factorial of ", num, " = ", product
end program factorial
